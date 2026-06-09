<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: system_settings
 * Skema sesuai 02_DATABASE.md §2.8
 * Key-value store untuk konfigurasi sistem yang dapat diubah via UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 50)->default('string');
            $table->string('group', 100)->default('general');
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
