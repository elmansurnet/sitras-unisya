<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * SystemSettingSeeder
 *
 * Seed semua konfigurasi sistem default.
 * Kolom sesuai migration 000007 (fix):
 *   key, value, type (ENUM), group, label, description, is_encrypted
 *
 * KRITIS:
 * - wa_gateway_url: diset ke URL UNISYA
 * - wa_api_key & wa_sender: dikosongkan, diisi admin via UI
 * - is_encrypted: 0 untuk semua default (admin aktifkan enkripsi via UI)
 * - type values: hanya 'string','integer','boolean','json','text' (sesuai ENUM migration)
 *   PERHATIAN: 'number' tidak valid — gunakan 'integer'
 */
class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ---------------------------------------------------------------
            // Identitas Universitas
            // ---------------------------------------------------------------
            [
                'key'          => 'university_name',
                'value'        => 'Universitas Islam Syarifuddin',
                'type'         => 'string',
                'group'        => 'university',
                'label'        => 'Nama Universitas',
                'description'  => 'Nama resmi universitas yang ditampilkan di seluruh sistem.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'university_tagline',
                'value'        => 'Tafaqquh Fiddin Walfikri',
                'type'         => 'string',
                'group'        => 'university',
                'label'        => 'Tagline Universitas',
                'description'  => 'Motto atau tagline universitas.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'university_logo_url',
                'value'        => '',
                'type'         => 'string',
                'group'        => 'university',
                'label'        => 'URL Logo Universitas',
                'description'  => 'Path logo universitas untuk header dan laporan.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'university_address',
                'value'        => 'Lumajang, Jawa Timur, Indonesia',
                'type'         => 'text',
                'group'        => 'university',
                'label'        => 'Alamat Universitas',
                'description'  => 'Alamat resmi universitas.',
                'is_encrypted' => 0,
            ],

            // ---------------------------------------------------------------
            // WhatsApp Gateway UNISYA
            // KRITIS: url diset, api_key & sender dikosongkan (diisi via UI)
            // ---------------------------------------------------------------
            [
                'key'          => 'wa_gateway_url',
                'value'        => 'https://wacenter.unisya.ac.id/send-message',
                'type'         => 'string',
                'group'        => 'whatsapp',
                'label'        => 'URL Gateway WhatsApp',
                'description'  => 'Endpoint WA Center UNISYA. Jangan ubah kecuali ada perubahan server.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'wa_api_key',
                'value'        => '',
                'type'         => 'string',
                'group'        => 'whatsapp',
                'label'        => 'API Key WhatsApp Gateway',
                'description'  => 'API key dari WA Center UNISYA. Wajib diisi oleh superadmin.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'wa_sender',
                'value'        => '',
                'type'         => 'string',
                'group'        => 'whatsapp',
                'label'        => 'Nomor Pengirim WhatsApp',
                'description'  => 'Nomor WA pengirim format 628xxxxxxxxxx. Wajib diisi oleh superadmin.',
                'is_encrypted' => 0,
            ],

            // ---------------------------------------------------------------
            // SMTP Email
            // ---------------------------------------------------------------
            [
                'key'          => 'smtp_host',
                'value'        => '',
                'type'         => 'string',
                'group'        => 'email',
                'label'        => 'SMTP Host',
                'description'  => 'Host server email (e.g. smtp.gmail.com).',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'smtp_port',
                'value'        => '587',
                'type'         => 'string',
                'group'        => 'email',
                'label'        => 'SMTP Port',
                'description'  => 'Port SMTP (587 untuk TLS, 465 untuk SSL).',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'smtp_username',
                'value'        => '',
                'type'         => 'string',
                'group'        => 'email',
                'label'        => 'Username SMTP',
                'description'  => 'Alamat email pengirim untuk autentikasi SMTP.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'smtp_password',
                'value'        => '',
                'type'         => 'string',
                'group'        => 'email',
                'label'        => 'Password SMTP',
                'description'  => 'Password email atau App Password. Aktifkan enkripsi via UI untuk field ini.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'smtp_from_name',
                'value'        => 'SITRAS UNISYA',
                'type'         => 'string',
                'group'        => 'email',
                'label'        => 'Nama Pengirim Email',
                'description'  => 'Nama yang muncul di field From pada email keluar.',
                'is_encrypted' => 0,
            ],
            [
                'key'          => 'smtp_from_address',
                'value'        => '',
                'type'         => 'string',
                'group'        => 'email',
                'label'        => 'Alamat Email Pengirim',
                'description'  => 'Alamat email pengirim (From address).',
                'is_encrypted' => 0,
            ],

            // ---------------------------------------------------------------
            // Tracer Study Settings
            // ---------------------------------------------------------------
            [
                'key'          => 'tracer_min_response_rate',
                'value'        => '60',
                'type'         => 'integer',         // FIX: 'number' bukan ENUM valid, gunakan 'integer'
                'group'        => 'tracer',
                'label'        => 'Target Response Rate (%)',
                'description'  => 'Target minimum persentase respons tracer study.',
                'is_encrypted' => 0,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
