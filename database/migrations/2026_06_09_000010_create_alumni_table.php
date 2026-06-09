<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alumni
 *
 * Skema sesuai 02_DATABASE.md §2.3
 * Security notes (07_SECURITY.md §2):
 *  - gpa         → DECIMAL(4,2)  — cast ke float di Model, return as number di API
 *  - foto_path   → disimpan di storage/app/private/ — akses via Storage::temporaryUrl()
 *  - lat/lng     → DECIMAL(10,7) — presisi koordinat GPS
 *  - survey_status → ENUM 4 nilai
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            // Primary Key
            $table->bigIncrements('id');

            // Relasi ke users
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Relasi ke study_programs
            $table->unsignedBigInteger('study_program_id')->nullable();
            $table->foreign('study_program_id')->references('id')->on('study_programs')->nullOnDelete();

            // Relasi ke graduation_years
            $table->unsignedBigInteger('graduation_year_id')->nullable();
            $table->foreign('graduation_year_id')->references('id')->on('graduation_years')->nullOnDelete();

            // Identitas
            $table->string('nim', 20)->unique();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();

            // Akademik
            $table->string('degree', 10)->default('S1'); // S1, S2, D3, dll
            $table->decimal('gpa', 4, 2)->nullable();    // 02_DATABASE.md §2.3: DECIMAL(4,2)
            $table->date('graduation_date')->nullable();
            $table->string('thesis_title', 255)->nullable();

            // Foto profil — disimpan di storage/app/private/
            $table->string('foto_path', 255)->nullable();

            // Koordinat untuk peta alumni
            $table->decimal('latitude', 10, 7)->nullable();   // DECIMAL(10,7)
            $table->decimal('longitude', 10, 7)->nullable();  // DECIMAL(10,7)

            // Status kerja
            $table->enum('employment_status', [
                'bekerja',
                'wirausaha',
                'melanjutkan_studi',
                'belum_bekerja',
            ])->nullable();

            // Status survei — 4 nilai sesuai 02_DATABASE.md §2.3
            $table->enum('survey_status', [
                'belum_disurvei',
                'terkirim',
                'sedang_mengisi',
                'selesai',
            ])->default('belum_disurvei');

            $table->timestamp('survey_sent_at')->nullable();
            $table->timestamp('survey_completed_at')->nullable();

            // Catatan admin
            $table->text('notes')->nullable();

            // Timestamps & SoftDeletes
            $table->timestamps();
            $table->softDeletes();

            // Index sesuai 02_DATABASE.md §2.3
            $table->index('user_id');
            $table->index('study_program_id');
            $table->index('graduation_year_id');
            $table->index('survey_status');
            $table->index('employment_status');
            $table->index('city');
            $table->index('province');
            $table->index(['latitude', 'longitude'], 'alumni_coordinates_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
