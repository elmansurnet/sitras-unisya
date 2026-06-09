<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: otp_codes
 * Skema sesuai 02_DATABASE.md §2.1
 *
 * KRITIS KEAMANAN:
 * Kolom `code` menyimpan SHA-256 hex digest dari OTP plaintext.
 * SHA-256 menghasilkan 64 karakter hex -> VARCHAR(64).
 * OTP plaintext HANYA dikirim ke user via WA/Email, TIDAK PERNAH disimpan.
 * Generate : random_int(100000, 999999)
 * Store    : hash('sha256', (string)$rawOtp)
 * Verify   : hash_equals(hash('sha256', $input), $stored)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('identifier', 255);
            // code stores SHA-256 hex digest (64 chars), BUKAN plaintext OTP
            $table->string('code', 64);
            $table->enum('purpose', ['login', 'verify', 'reset'])->default('login');
            $table->enum('channel', ['email', 'whatsapp'])->default('email');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            // Indexes (sesuai 02_DATABASE.md)
            $table->index('identifier');
            $table->index('expires_at');
            $table->index('user_id');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
