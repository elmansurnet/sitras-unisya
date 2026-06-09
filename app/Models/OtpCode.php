<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    /**
     * Hanya menyimpan created_at, tidak updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'identifier',
        'code',
        'purpose',
        'channel',
        'attempts',
        'is_used',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used'    => 'boolean',
            'attempts'   => 'integer',
            'created_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * OtpCode bisa tidak terikat ke user (anonymous OTP untuk verify awal).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    /**
     * OTP aktif: belum digunakan, belum expired, belum exceed max attempts.
     *
     * @param  Builder<OtpCode>  $query
     * @return Builder<OtpCode>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', config('tracer.otp.max_attempts', 3));
    }
}
