<?php

namespace App\Services;

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
     * Proxy publik ke AlumniRepository::paginate() agar Controller
     * tidak perlu mengakses $alumniRepo secara langsung (private).
     *
     * @param  array<string,mixed> $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->alumniRepo->paginate($filters);
    }

    /**
     * Statistik ringkas alumni untuk dashboard.
     * Proxy publik ke AlumniRepository::stats().
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
     * @param  array<string,mixed> $data      Validated data dari StoreAlumniRequest
     * @param  int                 $actorId   user_id yang melakukan aksi
     */
    public function create(array $data, int $actorId): Alumni
    {
        return DB::transaction(function () use ($data, $actorId) {
            $user = User::create([
                'name'      => $data['full_name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password'] ?? Str::random(16)),
                'role'      => 'alumni',
                'is_active' => true,
            ]);

            $alumni = Alumni::create(array_merge(
                collect($data)->except(['email', 'password', 'photo'])->toArray(),
                ['user_id' => $user->id, 'is_active' => true]
            ));

            // Upload foto jika disertakan saat create
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $this->uploadPhoto($alumni, $data['photo']);
                $alumni->refresh();
            }

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

            // Upload foto jika disertakan saat update
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $this->uploadPhoto($alumni, $data['photo']);
            }

            $alumni->update(collect($data)->except(['email', 'password', 'photo'])->toArray());

            return $alumni->fresh(['user', 'studyProgram.faculty', 'graduationYear']);
        });
    }

    /**
     * Soft-delete alumni (dan user terkait).
     */
    public function delete(Alumni $alumni, int $actorId): void
    {
        DB::transaction(function () use ($alumni, $actorId) {
            // Hapus foto dari private storage sebelum delete
            if ($alumni->photo_path && Storage::disk('private')->exists($alumni->photo_path)) {
                Storage::disk('private')->delete($alumni->photo_path);
            }
            $alumni->delete();
            $alumni->user?->delete();
        });
    }

    // ─── FOTO ─────────────────────────────────────────────────────────────────

    /**
     * Upload foto profil alumni ke storage/app/private/alumni/photos/.
     * Akses via signed URL — TIDAK boleh di public/.
     * Max 2MB, MIME: jpg/jpeg/png/webp (divalidasi di StoreAlumniRequest/UpdateAlumniRequest).
     *
     * @param  Alumni       $alumni
     * @param  UploadedFile $file
     * @return string        Path relatif di disk 'private'
     */
    public function uploadPhoto(Alumni $alumni, UploadedFile $file): string
    {
        // Hapus foto lama jika ada
        if ($alumni->photo_path && Storage::disk('private')->exists($alumni->photo_path)) {
            Storage::disk('private')->delete($alumni->photo_path);
        }

        $filename = Str::uuid() . '.' . $file->extension();

        // storeAs() adalah cara benar menyimpan dengan nama custom di disk tertentu
        $path = $file->storeAs(
            'alumni/photos',
            $filename,
            'private'
        );

        $alumni->update(['photo_path' => $path]);

        AuditLog::record(
            action   : 'upload_photo',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: ['photo_path' => $alumni->getOriginal('photo_path')],
            newValues: ['photo_path' => $path],
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
        $batchId = 'import_' . now()->format('Ymd_His') . '_' . Str::random(6);

        DB::transaction(function () use ($validRows, $batchId, $actorId, &$successCount) {
            foreach ($validRows as $row) {
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
                        'is_active'    => true,
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
     * Export data alumni ke Excel via queue job.
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
     * Generate template Excel untuk import alumni.
     */
    public function generateImportTemplate(): string
    {
        return $this->importExport->generateTemplate('alumni');
    }

    // ─── UNDANGAN SURVEI ──────────────────────────────────────────────────────

    /**
     * Kirim undangan survei ke alumni.
     * Dispatch via NotificationService → queue 'notifications'.
     *
     * @param  Alumni $alumni
     * @param  int    $surveyPeriodId  ID dari survey_periods
     * @param  int    $actorId
     */
    public function sendInvitation(Alumni $alumni, int $surveyPeriodId, int $actorId): void
    {
        $surveyPeriod = SurveyPeriod::findOrFail($surveyPeriodId);

        // Dispatch notifikasi ke queue melalui NotificationService
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
