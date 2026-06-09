<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: system_settings
 * Skema sesuai 02_DATABASE.md §2.8
 *
 * Key-value store untuk konfigurasi sistem yang dapat diubah via UI.
 * Kolom is_encrypted: nilai dienkripsi menggunakan Laravel encrypt() jika 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Kunci unik pengaturan
            $table->string('key', 100)->unique();

            // Nilai — NULL diperbolehkan
            $table->text('value')->nullable();

            // Tipe data nilai — ENUM sesuai 02_DATABASE.md §2.8
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'text'])->default('string');

            // Grup pengelompokan UI (smtp, whatsapp, general, security, dll.)
            $table->string('group', 50)->nullable();

            // Label dan deskripsi untuk UI admin
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();

            // Enkripsi — nilai dienkripsi dengan Laravel encrypt() jika 1
            // Sesuai 02_DATABASE.md §2.8: is_encrypted TINYINT(1) NO 0
            $table->tinyInteger('is_encrypted')->default(0)
                  ->comment('1 = nilai dienkripsi dengan Laravel encrypt()');

            $table->timestamps();

            // UNIQUE(key) sudah otomatis dari ->unique() di atas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
