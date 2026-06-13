<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_public'    => 'boolean',
            'is_encrypted' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // CONDITIONAL ENCRYPTION — 02_DATABASE.md §2.8
    //
    // is_encrypted adalah flag PER-ROW (TINYINT 0/1).
    // Enkripsi hanya berlaku pada baris yang is_encrypted = 1.
    // Tidak menggunakan cast 'encrypted' karena itu akan mengenkripsi
    // semua value termasuk setting non-sensitif seperti app_name.
    //
    // Setting sensitif: wa_api_key, smtp_password, wa_sender
    // Setting publik:   app_name, app_url, default_channel, dll.
    // -----------------------------------------------------------------

    /**
     * Accessor — otomatis decrypt saat membaca value jika is_encrypted = 1.
     */
    public function getValueAttribute(mixed $rawValue): mixed
    {
        if ($this->is_encrypted && ! is_null($rawValue)) {
            try {
                return Crypt::decryptString((string) $rawValue);
            } catch (\Throwable) {
                // Nilai sudah plaintext (misal sebelum enkripsi pertama kali diterapkan)
                return $rawValue;
            }
        }

        return $rawValue;
    }

    /**
     * Mutator — otomatis encrypt saat menyimpan value jika is_encrypted = 1.
     * Dipanggil setiap kali $model->value = $something.
     */
    public function setValueAttribute(mixed $rawValue): void
    {
        if ($this->is_encrypted && ! is_null($rawValue)) {
            $this->attributes['value'] = Crypt::encryptString((string) $rawValue);
        } else {
            $this->attributes['value'] = $rawValue;
        }
    }

    // -----------------------------------------------------------------
    // STATIC HELPERS
    // Cache 60 menit. Cache key menyertakan nama field agar tidak
    // tercampur dengan key lain di cache store yang sama.
    // -----------------------------------------------------------------

    /**
     * Ambil nilai setting berdasarkan key.
     * Otomatis di-decrypt jika is_encrypted = 1 (via accessor di atas).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            'system_setting_' . $key,
            now()->addMinutes(60),
            static function () use ($key, $default): mixed {
                $setting = static::where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                // Akses via property agar accessor getValueAttribute() aktif
                return $setting->value ?? $default;
            }
        );
    }

    /**
     * Simpan nilai setting dan bust cache.
     * Jika baris sudah ada dan is_encrypted = 1, nilai akan dienkripsi oleh mutator.
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $value;  // mutator setValueAttribute() dipanggil di sini
        $setting->save();

        Cache::forget('system_setting_' . $key);
    }
}
