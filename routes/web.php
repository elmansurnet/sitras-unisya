<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| SITRAS UNISYA adalah SPA (Single Page Application) berbasis Vue 3.
| Semua navigasi dihandle oleh Vue Router di sisi client.
| Laravel hanya perlu melayani satu halaman HTML (app.blade.php).
|
| Pengecualian:
|   - /api/*        → ditangani routes/api.php (Sanctum)
|   - /up           → health check (bootstrap/app.php)
|   - /storage/*    → symlink public storage
|
*/

// SPA catch-all — semua request non-API diarahkan ke Vue Router
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
