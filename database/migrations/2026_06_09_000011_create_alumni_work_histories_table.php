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
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_work_histories', function (Blueprint $table) {
            // Primary Key
            $table->bigIncrements('id');

            // Relasi ke alumni (required)
            $table->unsignedBigInteger('alumni_id');
            $table->foreign('alumni_id')
                  ->references('id')
                  ->on('alumni')
                  ->cascadeOnDelete();

            // Relasi ke employers (nullable — employer mungkin belum terdaftar di sistem)
            // FK CONSTRAINT ke employers ditambahkan di sesi 2B setelah tabel employers dibuat
            $table->unsignedBigInteger('employer_id')->nullable();

            // Data pekerjaan — sesuai 02_DATABASE.md §2.3
            $table->string('company_name', 255);                // VARCHAR(255) sesuai spec
            $table->string('position', 255);                    // VARCHAR(255) sesuai spec
            $table->string('industry_sector', 100)->nullable(); // nama kolom sesuai spec
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
            $table->date('end_date')->nullable(); // NULL = masih bekerja
            $table->tinyInteger('is_current')->unsigned()->default(0);

            // Lokasi
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->nullable();         // kolom sesuai spec

            // Gaji — sesuai spec: VARCHAR(50) kode range, bukan FK
            $table->string('monthly_salary_range', 50)->nullable(); // contoh: '3_5jt'

            // Relevansi & tunggu kerja
            $table->tinyInteger('is_relevant_to_study')->unsigned()->nullable(); // 1=ya,0=tidak
            $table->tinyInteger('waiting_time_months')->unsigned()->nullable();  // bulan tunggu

            // Deskripsi
            $table->text('description')->nullable();

            // Timestamps
            $table->timestamps();

            // Index sesuai 02_DATABASE.md §2.3
            $table->index('alumni_id');
            $table->index('employer_id'); // index kolom ada, constraint FK menyusul di sesi 2B
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_work_histories');
    }
};
