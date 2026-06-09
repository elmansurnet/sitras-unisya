<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alumni_work_histories
 *
 * Skema sesuai 02_DATABASE.md §2.3
 * Relasi ke employers nullable — employer bisa belum terdaftar di sistem.
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
            $table->foreign('alumni_id')->references('id')->on('alumni')->cascadeOnDelete();

            // Relasi ke employers (nullable — mungkin belum terdaftar)
            $table->unsignedBigInteger('employer_id')->nullable();
            $table->foreign('employer_id')->references('id')->on('employers')->nullOnDelete();

            // Data pekerjaan
            $table->string('company_name', 150);
            $table->string('position', 100);
            $table->string('industry', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();

            // Gaji — FK ke salary_ranges nullable
            $table->unsignedBigInteger('salary_range_id')->nullable();
            $table->foreign('salary_range_id')->references('id')->on('salary_ranges')->nullOnDelete();

            // Periode kerja
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = masih bekerja
            $table->boolean('is_current')->default(false);

            // Sumber data
            $table->enum('source', ['alumni', 'admin', 'survey'])->default('alumni');

            // Timestamps
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
