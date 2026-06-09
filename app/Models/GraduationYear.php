<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
