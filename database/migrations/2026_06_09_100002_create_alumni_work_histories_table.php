<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel alumni_work_histories: riwayat pekerjaan alumni.
     * employer_id: NULLABLE — employer mungkin belum terdaftar di sistem.
     * is_relevant_to_study: TINYINT(1) NULLABLE — 1=ya, 0=tidak, NULL=belum diisi.
     */
    public function up(): void
    {
        Schema::create('alumni_work_histories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('alumni_id')
                  ->constrained('alumni')
                  ->cascadeOnDelete();

            // Nullable: employer mungkin belum terdaftar di sistem (dibuat di sesi 2B)
            $table->foreignId('employer_id')
                  ->nullable()
                  ->constrained('employers')
                  ->nullOnDelete();

            // Data pekerjaan
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

            $table->date('start_date');
            $table->date('end_date')->nullable()->comment('NULL = masih bekerja');
            $table->tinyInteger('is_current')->default(0)->comment('0=tidak, 1=ya');

            // Lokasi
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->nullable();

            // Gaji
            $table->string('monthly_salary_range', 50)->nullable()->comment('Kode rentang gaji, misal: 3-5jt');

            // CRITICAL: TINYINT(1) NULLABLE — 1=ya, 0=tidak, NULL=belum diisi
            $table->tinyInteger('is_relevant_to_study')
                  ->nullable()
                  ->comment('1=relevan, 0=tidak relevan, NULL=belum diisi');

            $table->tinyInteger('waiting_time_months')
                  ->unsigned()
                  ->nullable()
                  ->comment('Bulan menunggu setelah lulus hingga bekerja');

            $table->text('description')->nullable();

            $table->timestamps();

            // Index sesuai 02_DATABASE.md §2.3
            $table->index('alumni_id');
            $table->index('employer_id');
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_work_histories');
    }
};
