<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    protected $fillable = [
        'faculty_id',
        'code',
        'name',
        'degree_level',
        'accreditation',
        'head_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Relationship ke Alumni didefinisikan di sesi 2A saat model Alumni dibuat.
     * Placeholder method ini akan diisi saat sesi 2A.
     */
    public function alumni(): HasMany
    {
        return $this->hasMany(Alumni::class);
    }
}
