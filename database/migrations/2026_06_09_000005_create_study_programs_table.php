<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: study_programs
 * Skema sesuai 02_DATABASE.md §2.2
 * FK ke faculties (dependency: 000004)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('faculty_id');
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->enum('degree_level', ['D3', 'D4', 'S1', 'S2', 'S3', 'Profesi']);
            $table->string('accreditation', 10)->nullable();
            $table->string('head_name', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes (sesuai 02_DATABASE.md)
            $table->index('faculty_id');

            $table->foreign('faculty_id')
                  ->references('id')
                  ->on('faculties')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_programs');
    }
};
