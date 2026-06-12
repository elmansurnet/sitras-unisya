<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'template_id',
        'type',
        'recipient',
        'recipient_type',
        'recipient_id',
        'subject',
        'body',
        'status',
        'error_message',
        'sent_at',
        'provider_response',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at'           => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByRecipientType($query, string $recipientType)
    {
        return $query->where('recipient_type', $recipientType);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Ambil message_id dari provider_response (khusus WA Gateway UNISYA).
     * Format response gateway: { status: true, data: { key: { id: "..." } } }
     */
    public function getWaMessageIdAttribute(): ?string
    {
        if (empty($this->provider_response)) {
            return null;
        }

        $data = $this->provider_response['data'] ?? [];
        foreach ($data as $item) {
            return $item['id'] ?? null;
        }

        return null;
    }

    public function getIsSuccessAttribute(): bool
    {
        return in_array($this->status, ['sent', 'delivered']);
    }
}
