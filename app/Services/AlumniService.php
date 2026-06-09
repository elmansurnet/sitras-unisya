<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\AlumniRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlumniService
{
    public function __construct(
        private readonly AlumniRepository   $alumniRepo,
        private readonly ImportExportService $importExport,
    ) {}

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    /**
     * Buat alumni baru beserta user account-nya.
     *
     * @param  array<string,mixed> $data  Validated data dari StoreAlumniRequest
     * @param  int                 $actorId  user_id yang melakukan aksi
     */
    public function create(array $data, int $actorId): Alumni
    {
        return DB::transaction(function () use ($data, $actorId) {
            // 1. Buat akun user
            $user = User::create([
                'name'     => $data['full_name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password'] ?? Str::random(16)),
                'role'     => 'alumni',
                'is_active' => true,
            ]);

            // 2. Buat record alumni (observer AlumniObserver::created akan log audit)
            $alumni = Alumni::create(array_merge(
                collect($data)->except(['email', 'password'])->toArray(),
                ['user_id' => $user->id, 'is_active' => true]
            ));

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
            // Update email di tabel users jika berubah
            if (isset($data['email']) && $data['email'] !== $alumni->user?->email) {
                $alumni->user?->update(['email' => $data['email']]);
            }

            // Observer akan tangkap old/new values untuk audit log
            $alumni->update(collect($data)->except(['email', 'password'])->toArray());

            return $alumni->fresh(['user', 'studyProgram.faculty', 'graduationYear']);
        });
    }

    /**
     * Soft-delete alumni (dan user terkait).
     */
    public function delete(Alumni $alumni, int $actorId): void
    {
        DB::transaction(function () use ($alumni, $actorId) {
            $alumni->delete();
            $alumni->user?->delete();
        });
    }

    // ─── FOTO ─────────────────────────────────────────────────────────────────

    /**
     * Upload foto profil alumni.
     * Disimpan di storage/app/private/alumni/photos/ — akses via signed URL.
     * Max 2MB, mime: jpg/jpeg/png/webp.
     */
    public function uploadPhoto(Alumni $alumni, UploadedFile $file): string
    {
        // Hapus foto lama jika ada
        if ($alumni->photo_path && Storage::exists($alumni->photo_path)) {
            Storage::delete($alumni->photo_path);
        }

        $path = $file->store(
            'alumni/photos',
            ['disk' => 'private', 'filename' => Str::uuid() . '.' . $file->extension()]
        );

        $alumni->update(['photo_path' => $path]);

        AuditLog::record(
            action   : 'upload_photo',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: ['photo_path' => null],
            newValues: ['photo_path' => $path],
            modelType: Alumni::class,
        );

        return $path;
    }

    // ─── IMPORT / EXPORT ──────────────────────────────────────────────────────

    /**
     * Import alumni dari file Excel.
     * Return ringkasan: berhasil, gagal, errors.
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
     * Export data alumni ke Excel.
     * Dispatch job ke queue untuk file besar.
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
     * Kirim ulang undangan survei ke alumni.
     * Job dikirim ke queue 'high' agar prioritas tinggi.
     */
    public function sendInvitation(Alumni $alumni, int $surveyPeriodId, int $actorId): void
    {
        // Akan diimplementasi penuh di sesi 4A setelah SurveyPeriod model tersedia
        // Placeholder dispatch:
        AuditLog::record(
            action   : 'send_invitation',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: null,
            newValues: ['survey_period_id' => $surveyPeriodId, 'actor_id' => $actorId],
            modelType: Alumni::class,
        );
    }
}
