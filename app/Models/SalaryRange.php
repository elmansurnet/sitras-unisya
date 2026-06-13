<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRange extends Model
{
    protected $fillable = [
        'label',
        'min_value',
        'max_value',
        'order_number',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_value'    => 'integer',
            'max_value'    => 'integer',
            'order_number' => 'integer',
            'is_active'    => 'boolean',
        ];
    }
}
