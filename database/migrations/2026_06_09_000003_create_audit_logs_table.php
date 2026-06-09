<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: audit_logs
 * Skema sesuai 02_DATABASE.md §2.1
 *
 * PENTING: Tabel ini bersifat APPEND-ONLY.
 * Tidak ada UPDATE atau DELETE yang diizinkan.
 * Tidak ada SoftDeletes.
 * Tidak ada updated_at (hanya created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_role', 50)->nullable();
            $table->string('action', 100);
            $table->string('module', 100);
            $table->string('model_type', 255)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            // Indexes (sesuai 02_DATABASE.md)
            $table->index('user_id');
            $table->index('action');
            $table->index('module');
            $table->index('created_at');
            $table->index(['model_type', 'model_id']);

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
