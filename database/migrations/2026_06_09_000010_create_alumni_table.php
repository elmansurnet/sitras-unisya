<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alumni
 *
 * Skema sesuai 02_DATABASE.md §2.3 — TOTAL REWRITE
 *
 * Changelog fix vs versi sebelumnya:
 * - Ganti 'name' VARCHAR(100) → 'full_name' VARCHAR(255)
 * - Tambah: nik, birth_place, graduation_predicate, linkedin_url, import_batch
 * - Ganti 'address'+city+province → address_street/village/district/city/province/postal_code
 * - Ganti latitude/longitude → address_latitude/address_longitude
 * - Ganti foto_path → photo (sesuai naming spec)
 * - Hapus kolom tidak di spec: whatsapp, employment_status, degree, graduation_date,
 *   survey_sent_at, survey_completed_at, notes
 * - study_program_id & graduation_year_id: NOT NULL (required per spec §2.3)
 *
 * Security notes (07_SECURITY.md §2):
 *  - gpa             → DECIMAL(4,2) — cast ke float di Model, return as number di API
 *  - photo           → disimpan di storage/app/private/ — akses via Storage::temporaryUrl()
 *  - address_lat/lng → DECIMAL(10,7) — presisi koordinat GPS
 *  - survey_status   → ENUM 4 nilai
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            // Primary Key
            $table->bigIncrements('id');

            // FK ke users (one-to-one, nullable — alumni bisa diimport sebelum punya akun)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // FK ke study_programs — REQUIRED
            $table->unsignedBigInteger('study_program_id');
            $table->foreign('study_program_id')
                  ->references('id')
                  ->on('study_programs')
                  ->restrictOnDelete();

            // FK ke graduation_years — REQUIRED
            $table->unsignedBigInteger('graduation_year_id');
            $table->foreign('graduation_year_id')
                  ->references('id')
                  ->on('graduation_years')
                  ->restrictOnDelete();

            // Identitas — sesuai 02_DATABASE.md §2.3
            $table->string('nim', 20)->unique();
            $table->string('nik', 20)->nullable();
            $table->string('full_name', 255);
            $table->enum('gender', ['L', 'P']);
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();

            // Akademik
            $table->text('thesis_title')->nullable();
            $table->decimal('gpa', 4, 2)->nullable();              // DECIMAL(4,2) — 0.00–4.00
            $table->string('graduation_predicate', 50)->nullable();

            // Alamat — 6 kolom terpisah sesuai 02_DATABASE.md §2.3
            $table->text('address_street')->nullable();
            $table->string('address_village', 100)->nullable();
            $table->string('address_district', 100)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_province', 100)->nullable();
            $table->string('address_postal_code', 10)->nullable();

            // Koordinat GPS — DECIMAL(10,7) sesuai spec
            $table->decimal('address_latitude', 10, 7)->nullable();
            $table->decimal('address_longitude', 10, 7)->nullable();

            // Kontak aktif
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();

            // Profil publik
            $table->string('linkedin_url', 255)->nullable();
            $table->string('photo', 255)->nullable();              // path relatif ke storage/app/private/

            // Status survei — 4 nilai ENUM sesuai 02_DATABASE.md §2.3
            $table->enum('survey_status', [
                'belum_disurvei',
                'terkirim',
                'sedang_mengisi',
                'selesai',
            ])->default('belum_disurvei');

            // Import batch — untuk tracing sumber data
            $table->string('import_batch', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index sesuai 02_DATABASE.md §2.3
            // CATATAN: user_id, study_program_id, graduation_year_id sudah punya index
            // otomatis dari FK declaration di InnoDB — index manual di bawah untuk
            // memastikan eksplisit dan kompatibel dengan semua engine.
            $table->index('user_id');
            $table->index('study_program_id');
            $table->index('graduation_year_id');
            $table->index('survey_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
