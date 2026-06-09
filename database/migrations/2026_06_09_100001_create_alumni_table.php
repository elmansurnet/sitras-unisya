<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel alumni: data utama lulusan universitas.
     * gpa: DECIMAL(4,2) — BUKAN VARCHAR. Cast 'decimal:2' di model.
     * survey_status: ENUM 4 nilai sesuai 02_DATABASE.md §2.3.
     * address_latitude/longitude: DECIMAL(10,7) untuk akurasi GPS.
     */
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relasi ke users (one-to-one)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Identitas
            $table->string('nim', 20)->unique();
            $table->string('nik', 20)->nullable();
            $table->string('fullname', 255);
            $table->enum('gender', ['L', 'P']);
            $table->string('birthplace', 100)->nullable();
            $table->date('birth_date')->nullable();

            // Akademik
            $table->foreignId('study_program_id')
                  ->constrained('study_programs');
            $table->foreignId('graduation_year_id')
                  ->constrained('graduation_years');
            $table->text('thesis_title')->nullable();

            // CRITICAL: DECIMAL(4,2) bukan VARCHAR
            $table->decimal('gpa', 4, 2)->nullable()->comment('IPK 0.00-4.00, DECIMAL(4,2)');

            $table->string('graduation_predicate', 50)->nullable();

            // Alamat
            $table->text('address_street')->nullable();
            $table->string('address_village', 100)->nullable();
            $table->string('address_district', 100)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_province', 100)->nullable();
            $table->string('address_postal_code', 10)->nullable();

            // Koordinat GPS: DECIMAL(10,7) untuk akurasi ~11mm
            $table->decimal('address_latitude', 10, 7)->nullable();
            $table->decimal('address_longitude', 10, 7)->nullable();

            // Kontak
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('photo', 255)->nullable()->comment('Path relatif ke storage, bukan URL publik');

            // Status survei: 4 nilai ENUM
            $table->enum('survey_status', [
                'belum_disurvei',
                'terkirim',
                'sedang_mengisi',
                'selesai',
            ])->default('belum_disurvei');

            // Import tracing
            $table->string('import_batch', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index sesuai 02_DATABASE.md §2.3
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
