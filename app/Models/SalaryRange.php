<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRange extends Model
{
    protected $fillable = [
        'label',
        'min_salary',
        'max_salary',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_salary' => 'integer',
            'max_salary' => 'integer',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }
}
