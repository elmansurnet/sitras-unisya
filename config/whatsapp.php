<?php

/**
 * config/whatsapp.php
 * Konfigurasi WhatsApp Gateway UNISYA.
 *
 * Gateway: https://wacenter.unisya.ac.id/send-message
 * Method : POST JSON
 * Params : api_key, sender, number, message, footer (optional), full=1
 * Response: { status: true/false, data: { key: { id: "..." } } }
 *
 * CATATAN: Status 'delivered' TIDAK diisi otomatis dari gateway ini.
 * Nilai api_key dan sender diisi via UI (system_settings table),
 * bukan hardcode di .env production.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Gateway URL
    |--------------------------------------------------------------------------
    */
    'url' => env('WHATSAPP_GATEWAY_URL', 'https://wacenter.unisya.ac.id/send-message'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    | Nilai runtime diambil dari system_settings (key: wa_api_key).
    | .env hanya digunakan sebagai fallback awal / development.
    */
    'api_key' => env('WHATSAPP_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Sender Number
    |--------------------------------------------------------------------------
    | Format: 628xxxxxxxxxx (tanpa + atau spasi)
    | Nilai runtime diambil dari system_settings (key: wa_sender).
    */
    'sender' => env('WHATSAPP_SENDER', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'timeout'     => 30,
    'retry'       => 2,
    'retry_sleep' => 1000,

];
