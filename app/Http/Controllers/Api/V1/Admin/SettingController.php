<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\BulkUpdateSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    /**
     * Kunci yang mengandung data sensitif — value-nya di-mask di response.
     */
    private const SENSITIVE_KEYS = [
        'wa_api_key',
        'wa_sender',
        'mail_password',
        'mail_username',
        'app_secret',
    ];

    /**
     * GET /api/v1/admin/settings
     * Daftar semua setting, opsional filter by group.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('superadmin-only');

        $query = SystemSetting::query()->orderBy('group')->orderBy('key');

        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        $settings = $query->get()->map(fn ($s) => $this->maskSensitive($s));

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }

    /**
     * GET /api/v1/admin/settings/{key}
     * Detail satu setting berdasarkan key (bukan ID).
     */
    public function show(string $key): JsonResponse
    {
        Gate::authorize('superadmin-only');

        $setting = SystemSetting::where('key', $key)->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $this->maskSensitive($setting),
        ]);
    }

    /**
     * PUT /api/v1/admin/settings/{key}
     * Update nilai satu setting berdasarkan key.
     */
    public function update(UpdateSettingRequest $request, string $key): JsonResponse
    {
        $setting   = SystemSetting::where('key', $key)->firstOrFail();
        $oldValue  = $setting->value;

        SystemSetting::set($key, $request->validated()['value']);
        $setting->refresh();

        AuditLog::record(
            action: 'updated',
            module: 'system_setting',
            modelId: $setting->id,
            oldValues: ['key' => $key, 'value' => $this->isSensitive($key) ? '***' : $oldValue],
            newValues: ['key' => $key, 'value' => $this->isSensitive($key) ? '***' : $setting->value],
            modelType: SystemSetting::class
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil diperbarui.',
            'data'    => $this->maskSensitive($setting),
        ]);
    }

    /**
     * PATCH /api/v1/admin/settings/bulk
     * Update banyak setting sekaligus dalam satu request.
     */
    public function bulkUpdate(BulkUpdateSettingRequest $request): JsonResponse
    {
        $updated = [];

        foreach ($request->validated()['settings'] as $item) {
            $key     = $item['key'];
            $setting = SystemSetting::where('key', $key)->first();

            if (! $setting) {
                continue;
            }

            $oldValue = $setting->value;
            SystemSetting::set($key, $item['value']);
            $setting->refresh();

            AuditLog::record(
                action: 'bulk_updated',
                module: 'system_setting',
                modelId: $setting->id,
                oldValues: ['key' => $key, 'value' => $this->isSensitive($key) ? '***' : $oldValue],
                newValues: ['key' => $key, 'value' => $this->isSensitive($key) ? '***' : $setting->value],
                modelType: SystemSetting::class
            );

            $updated[] = $this->maskSensitive($setting->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => count($updated).' pengaturan berhasil diperbarui.',
            'data'    => $updated,
        ]);
    }

    // -------------------------------------------------------------------------

    private function isSensitive(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true);
    }

    private function maskSensitive(SystemSetting $setting): SystemSetting
    {
        if ($this->isSensitive($setting->key)) {
            $setting = clone $setting;
            $setting->value = '***';
        }

        return $setting;
    }
}
