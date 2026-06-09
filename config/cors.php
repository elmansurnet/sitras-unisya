<?php

/**
 * config/cors.php
 * Konfigurasi CORS sesuai 07_SECURITY.md §10.
 * allowed_origins dari FRONTEND_URL env variable.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    | Hanya domain frontend UNISYA yang diizinkan.
    | supports_credentials: true untuk Sanctum cookie-based auth.
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-API-Version',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];
