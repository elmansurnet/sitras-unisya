<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * AuditLog Model
 * Append-only — TIDAK boleh ada update atau delete.
 * Tidak menggunakan SoftDeletes.
 * Implementasi sesuai 07_SECURITY.md §8.3
 */
class AuditLog extends Model
{
    /**
     * Hanya created_at yang relevan, tidak updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_role',
        'action',
        'module',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // =========================================================================
    // Static Helper (sesuai 07_SECURITY.md §8.3)
    // =========================================================================

    /**
     * Record an audit log entry.
     *
     * @param  string       $action     e.g. 'login', 'update', 'delete'
     * @param  string       $module     e.g. 'auth', 'alumni', 'survey'
     * @param  int|null     $modelId    Primary key of affected record
     * @param  array|null   $oldValues  State before change
     * @param  array|null   $newValues  State after change
     * @param  string|null  $modelType  Fully-qualified class name of the model
     */
    public static function record(
        string $action,
        string $module,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $modelType = null
    ): self {
        /** @var User|null $user */
        $user = Auth::user();

        return static::create([
            'user_id'    => $user?->id,
            'user_role'  => $user?->role,
            'action'     => $action,
            'module'     => $module,
            'model_type' => $modelType,
            'model_id'   => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
