<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * Menggunakan $fillable (bukan $guarded) sesuai aturan proyek.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'email_verified_at',
        'is_active',
        'last_login_at',
        'login_attempts',
        'locked_until',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'locked_until'      => 'datetime',
            'is_active'         => 'boolean',
            'login_attempts'    => 'integer',
            'password'          => 'hashed',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function alumni(): HasOne
    {
        return $this->hasOne(Alumni::class);
    }

    public function employer(): HasOne
    {
        return $this->hasOne(Employer::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // =========================================================================
    // Helper Methods (sesuai spesifikasi 1A.11)
    // =========================================================================

    /**
     * Cek apakah akun sedang terkunci karena too many login attempts.
     */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Tambah hitungan login_attempts.
     * Jika sudah >= config('tracer.login.max_attempts'), kunci akun.
     */
    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
        $this->refresh();

        if ($this->login_attempts >= config('tracer.login.max_attempts', 5)) {
            $this->update([
                'locked_until' => now()->addMinutes(config('tracer.login.lockout_minutes', 15)),
            ]);
        }
    }

    /**
     * Reset login_attempts dan locked_until setelah login berhasil.
     */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'locked_until'   => null,
            'last_login_at'  => now(),
        ]);
    }

    /**
     * Cek apakah user berperan superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Cek apakah user berperan admin (termasuk superadmin).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }
}
