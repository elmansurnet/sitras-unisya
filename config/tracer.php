<?php

/**
 * config/tracer.php
 * Konfigurasi khusus aplikasi SITRAS UNISYA.
 * Baca dari .env dengan default values sesuai spesifikasi 07_SECURITY.md.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    | OTP generate: random_int(100000, 999999)
    | OTP store   : hash('sha256', $rawOtp) -> VARCHAR(64)
    | OTP verify  : hash_equals(hash('sha256', $input), $stored)
    */
    'otp' => [
        'expiry_minutes'          => (int) env('OTP_EXPIRY_MINUTES', 5),
        'max_attempts'            => (int) env('OTP_MAX_ATTEMPTS', 3),
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Lockout Configuration
    |--------------------------------------------------------------------------
    */
    'login' => [
        'max_attempts'    => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('LOGIN_LOCKOUT_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Employer Survey Token Configuration
    |--------------------------------------------------------------------------
    | Token: Str::random(64) — plaintext di employers.survey_token
    | One-survey use; expiry 30 hari dari generated_at
    */
    'employer_token' => [
        'expiry_days' => (int) env('EMPLOYER_TOKEN_EXPIRY_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_size_kb' => (int) env('MAX_UPLOAD_SIZE_KB', 10240),
        'disk'        => 'private',
    ],

];
