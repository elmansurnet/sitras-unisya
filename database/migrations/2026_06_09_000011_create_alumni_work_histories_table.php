<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alumni_work_histories
 *
 * Skema sesuai 02_DATABASE.md §2.3
 *
 * CATATAN FK employer_id:
 * Kolom employer_id (nullable) sudah ada di tabel ini, namun FOREIGN KEY CONSTRAINT
 * ke tabel employers BELUM ditambahkan di sini karena tabel employers dibuat di sesi 2B.
 * Constraint FK akan ditambahkan via migration terpisah di sesi 2B:
 *   2026_06_09_200003_add_fk_employer_to_alumni_work_histories.php
 *
 * Changelog fix vs versi sebelumnya:
 * - is_current: tinyInteger()->unsigned() → boolean() agar konsisten TINYINT(1)
 * - is_relevant_to_study: hapus ->unsigned() — TINYINT(1) tidak pakai unsigned
 * - Hapus duplikat $table->index('alumni_id') — FK di InnoDB otomatis membuat index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_work_histories', function (Blueprint $table) {
            // Primary Key
            $table->bigIncrements('id');

            // FK ke alumni (required, cascade delete)
            $table->unsignedBigInteger('alumni_id');
            $table->foreign('alumni_id')
                  ->references('id')
                  ->on('alumni')
                  ->cascadeOnDelete();

            // FK ke employers (nullable — employer mungkin belum terdaftar)
            // FK CONSTRAINT menyusul di sesi 2B via migration terpisah
            $table->unsignedBigInteger('employer_id')->nullable();

            // Data pekerjaan — sesuai 02_DATABASE.md §2.3
            $table->string('company_name', 255);
            $table->string('position', 255);
            $table->string('industry_sector', 100)->nullable();
            $table->enum('employment_type', [
                'penuh_waktu',
                'paruh_waktu',
                'kontrak',
                'freelance',
                'wirausaha',
                'magang',
            ])->nullable();

            // Periode kerja
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // FIX: boolean() menghasilkan TINYINT(1) — konsisten dengan konvensi boolean 02_DATABASE.md
            $table->boolean('is_current')->default(false);

            // Lokasi
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->nullable();

            // Gaji — VARCHAR(50) kode range (misal: '3_5jt'), bukan FK
            $table->string('monthly_salary_range', 50)->nullable();

            // FIX: hapus ->unsigned() pada TINYINT(1) boolean field
            $table->tinyInteger('is_relevant_to_study')->nullable()
                  ->comment('1=ya, 0=tidak, NULL=belum diisi');

            // Bulan menunggu setelah lulus — TINYINT UNSIGNED sesuai spec
            $table->tinyInteger('waiting_time_months')->unsigned()->nullable();

            $table->text('description')->nullable();
            $table->timestamps();

            // Index sesuai 02_DATABASE.md §2.3
            // alumni_id: TIDAK di-index manual — FK InnoDB otomatis membuat index
            $table->index('employer_id');  // manual karena FK belum ada (menyusul sesi 2B)
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_work_histories');
    }
};
