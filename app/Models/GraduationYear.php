<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GraduationYear extends Model
{
    protected $fillable = [
        'year',
        'academic_year',
        'semester',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year'      => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // RELATIONS
    // -----------------------------------------------------------------

    public function alumni(): HasMany
    {
        return $this->hasMany(Alumni::class);
    }
}
