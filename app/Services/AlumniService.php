<?php

namespace App\Services;

use App\Exports\AlumniExport;
use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\SurveyPeriod;
use App\Models\User;
use App\Repositories\AlumniRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlumniService
{
    public function __construct(
        private readonly AlumniRepository    $alumniRepo,
        private readonly ImportExportService $importExport,
        private readonly NotificationService $notificationService,
    ) {}

    // ─── PROXY: Repository methods untuk diakses dari Controller ──────────────

    /**
     * Ambil daftar alumni terpaginasi dengan filter.
     *
     * @param  array<string,mixed> $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->alumniRepo->paginate($filters);
    }

    /**
     * Statistik ringkas alumni untuk dashboard.
     *
     * @return array<string,int>
     */
    public function stats(): array
    {
        return $this->alumniRepo->stats();
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    /**
     * Buat alumni baru beserta user account-nya.
     *
     * @param  array<string,mixed> $data
     */
    public function create(array $data, int $actorId): Alumni
    {
        return DB::transaction(function () use ($data, $actorId) {
            // is_active milik tabel users, bukan tabel alumni
            $user = User::create([
                'name'      => $data['full_name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password'] ?? Str::random(16)),
                'role'      => 'alumni',
                'is_active' => true,
            ]);

            $alumni = Alumni::create(array_merge(
                collect($data)->except(['email', 'password', 'photo'])->toArray(),
                ['user_id' => $user->id]
            ));

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $this->uploadPhoto($alumni, $data['photo']);
                $alumni->refresh();
            }

            AuditLog::record(
                action   : 'create',
                module   : 'alumni',
                modelId  : $alumni->id,
                oldValues: null,
                newValues: ['nim' => $alumni->nim, 'full_name' => $alumni->full_name, 'actor_id' => $actorId],
                modelType: Alumni::class,
            );

            return $alumni->load(['user', 'studyProgram.faculty', 'graduationYear']);
        });
    }

    /**
     * Update data alumni.
     *
     * @param  array<string,mixed> $data
     */
    public function update(Alumni $alumni, array $data, int $actorId): Alumni
    {
        return DB::transaction(function () use ($alumni, $data, $actorId) {
            if (isset($data['email']) && $data['email'] !== $alumni->user?->email) {
                $alumni->user?->update(['email' => $data['email']]);
            }

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $this->uploadPhoto($alumni, $data['photo']);
            }

            $alumni->update(collect($data)->except(['email', 'password', 'photo'])->toArray());

            AuditLog::record(
                action   : 'update',
                module   : 'alumni',
                modelId  : $alumni->id,
                oldValues: null,
                newValues: ['actor_id' => $actorId],
                modelType: Alumni::class,
            );

            return $alumni->fresh(['user', 'studyProgram.faculty', 'graduationYear']);
        });
    }

    /**
     * Soft-delete alumni (dan user terkait).
     * Kolom foto di tabel alumni adalah `photo`, bukan `photo_path`.
     */
    public function delete(Alumni $alumni, int $actorId): void
    {
        DB::transaction(function () use ($alumni, $actorId) {
            if ($alumni->photo && Storage::disk('private')->exists($alumni->photo)) {
                Storage::disk('private')->delete($alumni->photo);
            }

            AuditLog::record(
                action   : 'delete',
                module   : 'alumni',
                modelId  : $alumni->id,
                oldValues: ['nim' => $alumni->nim, 'full_name' => $alumni->full_name],
                newValues: ['actor_id' => $actorId],
                modelType: Alumni::class,
            );

            $alumni->delete();
            $alumni->user?->delete();
        });
    }

    // ─── FOTO ─────────────────────────────────────────────────────────────────

    /**
     * Upload foto profil alumni ke storage/app/private/alumni/photos/.
     * Kolom di tabel alumni: `photo` (VARCHAR 255).
     */
    public function uploadPhoto(Alumni $alumni, UploadedFile $file): string
    {
        // Hapus foto lama jika ada
        if ($alumni->photo && Storage::disk('private')->exists($alumni->photo)) {
            Storage::disk('private')->delete($alumni->photo);
        }

        $filename = Str::uuid() . '.' . $file->extension();
        $path     = $file->storeAs('alumni/photos', $filename, 'private');

        $alumni->update(['photo' => $path]);

        AuditLog::record(
            action   : 'upload_photo',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: ['photo' => $alumni->getOriginal('photo')],
            newValues: ['photo' => $path],
            modelType: Alumni::class,
        );

        return $path;
    }

    // ─── IMPORT / EXPORT ──────────────────────────────────────────────────────

    /**
     * Import alumni dari file Excel.
     *
     * @return array{success: int, failed: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file, int $actorId): array
    {
        $rows = $this->importExport->parseExcel($file);
        [
            'valid'  => $validRows,
            'errors' => $errors,
        ] = $this->importExport->validateRows($rows, 'alumni');

        $successCount = 0;
        $batchId      = 'import_' . now()->format('Ymd_His') . '_' . Str::random(6);

        DB::transaction(function () use ($validRows, $batchId, $actorId, &$successCount) {
            foreach ($validRows as $row) {
                // is_active milik users, bukan alumni
                $user = User::create([
                    'name'      => $row['full_name'],
                    'email'     => $row['email'],
                    'password'  => Hash::make(Str::random(16)),
                    'role'      => 'alumni',
                    'is_active' => true,
                ]);

                Alumni::create(array_merge(
                    collect($row)->except(['email'])->toArray(),
                    [
                        'user_id'      => $user->id,
                        'import_batch' => $batchId,
                    ]
                ));

                $successCount++;
            }

            AuditLog::record(
                action   : 'import',
                module   : 'alumni',
                modelId  : null,
                oldValues: null,
                newValues: ['batch' => $batchId, 'count' => $successCount],
                modelType: Alumni::class,
            );
        });

        return [
            'success' => $successCount,
            'failed'  => count($errors),
            'errors'  => $errors,
        ];
    }

    /**
     * Export alumni via queue job (async).
     *
     * @param  array<string,mixed> $filters
     */
    public function export(array $filters, int $actorId): string
    {
        $filename = 'alumni_export_' . now()->format('Ymd_His') . '.xlsx';
        $path     = 'exports/' . $filename;

        \App\Jobs\GenerateReportExport::dispatch(
            type   : 'alumni',
            filters: $filters,
            path   : $path,
            userId : $actorId,
        )->onQueue('default');

        return $filename;
    }

    /**
     * Export alumni secara synchronous dan stream file ke browser.
     *
     * @param  array<string,mixed> $filters
     */
    public function exportStream(array $filters, int $actorId): BinaryFileResponse
    {
        $filename = 'alumni_export_' . now()->format('Ymd_His') . '.xlsx';

        $query = Alumni::with(['studyProgram', 'graduationYear'])
            ->when($filters['study_program_id']   ?? null, fn($q, $v) => $q->where('study_program_id', $v))
            ->when($filters['graduation_year_id'] ?? null, fn($q, $v) => $q->where('graduation_year_id', $v))
            ->when($filters['gender']             ?? null, fn($q, $v) => $q->where('gender', $v))
            ->orderBy('created_at', 'desc');

        $rows = $query->get()->map(fn(Alumni $a) => [
            $a->nim,
            $a->full_name,
            $a->gender === 'L' ? 'Laki-laki' : 'Perempuan',
            $a->studyProgram?->name ?? '-',
            $a->graduationYear?->year ?? '-',
            $a->gpa,
            $a->graduation_predicate ?? '-',
            $a->user?->email ?? '-',
            $a->phone ?? '-',
            $a->address_city ?? '-',
            $a->address_province ?? '-',
            $a->created_at?->format('d/m/Y') ?? '-',
        ]);

        AuditLog::record(
            action   : 'export',
            module   : 'alumni',
            modelId  : null,
            oldValues: null,
            newValues: ['filters' => $filters, 'count' => $rows->count(), 'actor_id' => $actorId],
            modelType: Alumni::class,
        );

        return Excel::download(new AlumniExport(collect($rows)), $filename);
    }

    /**
     * Generate template Excel untuk import alumni dan stream ke browser.
     */
    public function generateImportTemplate(): BinaryFileResponse
    {
        return $this->importExport->generateTemplate('alumni');
    }

    // ─── UNDANGAN SURVEI ──────────────────────────────────────────────────────

    /**
     * Kirim undangan survei ke alumni.
     */
    public function sendInvitation(Alumni $alumni, int $surveyPeriodId, int $actorId): void
    {
        $surveyPeriod = SurveyPeriod::findOrFail($surveyPeriodId);

        $this->notificationService->sendToAlumni(
            alumni       : $alumni,
            surveyPeriod : $surveyPeriod,
            templateEvent: 'survey_invitation',
        );

        AuditLog::record(
            action   : 'send_invitation',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: null,
            newValues: [
                'survey_period_id' => $surveyPeriodId,
                'survey_name'      => $surveyPeriod->name,
                'actor_id'         => $actorId,
            ],
            modelType: Alumni::class,
        );
    }
}
