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
        private readonly AlumniRepository  $repo,
        private readonly ImportExportService $importExport,
    ) {}

    /**
     * Buat alumni baru beserta user-nya.
     *
     * @param array $data Validated data dari StoreAlumniRequest
     * @return Alumni
     */
    public function create(array $data): Alumni
    {
        return DB::transaction(function () use ($data) {
            // Buat user alumni
            $user = User::create([
                'name'      => $data['full_name'],
                'email'     => $data['email'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'role'      => 'alumni',
                'password'  => isset($data['password'])
                               ? Hash::make($data['password'])
                               : null,
                'is_active' => true,
            ]);

            // Buat alumni
            $alumni = Alumni::create(array_merge(
                $data,
                ['user_id' => $user->id]
            ));

            AuditLog::record(
                action: 'create',
                module: 'Alumni',
                modelId: $alumni->id,
                newValues: $alumni->toArray(),
            );

            return $alumni->load(['user', 'studyProgram', 'graduationYear']);
        });
    }

    /**
     * Update data alumni.
     *
     * @param array $data Validated data dari UpdateAlumniRequest
     */
    public function update(Alumni $alumni, array $data): Alumni
    {
        return DB::transaction(function () use ($alumni, $data) {
            $oldValues = $alumni->toArray();

            // Sync kolom user jika ada perubahan
            $userChanges = array_filter([
                'name'  => $data['full_name'] ?? null,
                'email' => $data['email']     ?? null,
                'phone' => $data['phone']     ?? null,
            ], fn ($v) => $v !== null);

            if ($userChanges) {
                $alumni->user->update($userChanges);
            }

            $alumni->update($data);

            AuditLog::record(
                action: 'update',
                module: 'Alumni',
                modelId: $alumni->id,
                oldValues: $oldValues,
                newValues: $alumni->fresh()->toArray(),
            );

            return $alumni->fresh(['user', 'studyProgram', 'graduationYear']);
        });
    }

    /**
     * Soft-delete alumni.
     */
    public function delete(Alumni $alumni): void
    {
        DB::transaction(function () use ($alumni) {
            $oldValues = $alumni->toArray();

            $alumni->delete();

            AuditLog::record(
                action: 'delete',
                module: 'Alumni',
                modelId: $alumni->id,
                oldValues: $oldValues,
            );
        });
    }

    /**
     * Upload foto profil alumni.
     * File disimpan di storage/app/private/alumni/photos/
     */
    public function uploadPhoto(Alumni $alumni, UploadedFile $file): string
    {
        // Hapus foto lama jika ada
        if ($alumni->photo && Storage::exists($alumni->photo)) {
            Storage::delete($alumni->photo);
        }

        $path = $file->store('alumni/photos', 'private');

        $alumni->update(['photo' => $path]);

        return $path;
    }

    /**
     * Import alumni dari file Excel.
     *
     * @param  \Illuminate\Http\UploadedFile $file
     * @return array{ success: int, failed: int, errors: array }
     */
    public function import(UploadedFile $file): array
    {
        $batchId = 'IMP-' . Str::upper(Str::random(8)) . '-' . now()->format('Ymd');

        $rows = $this->importExport->parseExcel($file);
        ['valid' => $valid, 'errors' => $errors] = $this->importExport->validateRows($rows);

        if (!empty($valid)) {
            $this->importExport->batchInsert($valid, $batchId);
        }

        return [
            'batch_id' => $batchId,
            'success'  => count($valid),
            'failed'   => count($errors),
            'errors'   => $errors,
        ];
    }

    /**
     * Export alumni ke Excel.
     * Return path file di storage private.
     */
    public function export(array $filters = []): string
    {
        $alumni = $this->repo->findWithFilters($filters, perPage: 99999)->items();
        return $this->importExport->exportExcel($alumni);
    }

    /**
     * Kirim undangan survei ke alumni.
     * Mendispatch job queue, tidak langsung kirim.
     */
    public function sendInvitation(Alumni $alumni, int $surveyPeriodId, int $questionnaireId): void
    {
        // Dispatch job ke queue 'default'
        // Job SendAlumniSurveyInvitation dibuat di sesi 4A
        dispatch(new \App\Jobs\SendWhatsAppNotification(
            recipient: $alumni->phone ?? $alumni->user?->phone ?? '',
            templateEvent: 'survey_invitation',
            variables: [
                'nama_alumni'     => $alumni->full_name,
                'periode_survei'  => $surveyPeriodId,
                'link_survei'     => config('app.url') . '/survey',
            ],
            recipientType: 'alumni',
            recipientId: $alumni->id,
        ))->onQueue('default');

        $alumni->update(['survey_status' => 'terkirim']);
    }
}
