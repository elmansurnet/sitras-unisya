<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'event',
        'subject',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForChannel($query, string $type)
    {
        return $query->where('type', $type);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Apakah template ini untuk channel WhatsApp.
     */
    public function isWhatsApp(): bool
    {
        return $this->type === 'whatsapp';
    }

    /**
     * Apakah template ini untuk channel Email.
     */
    public function isEmail(): bool
    {
        return $this->type === 'email';
    }
}
