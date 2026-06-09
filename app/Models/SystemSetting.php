<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Ambil nilai setting berdasarkan key.
     * Cache 60 menit untuk performa.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            'system_setting_'.$key,
            now()->addMinutes(60),
            fn () => static::where('key', $key)->value('value') ?? $default
        );
    }

    /**
     * Set nilai setting dan bust cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget('system_setting_'.$key);
    }
}
