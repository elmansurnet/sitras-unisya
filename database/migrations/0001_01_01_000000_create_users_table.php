<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: users table
 * Skema sesuai 02_DATABASE.md §2.1
 * Menggantikan default Laravel users migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email', 255)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->enum('role', ['superadmin', 'admin', 'alumni', 'employer']);
            $table->string('password', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedTinyInteger('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes (sesuai 02_DATABASE.md)
            $table->index('role');
            $table->index('phone');
        });

        // Tabel sessions (dibutuhkan oleh SESSION_DRIVER=redis fallback)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
