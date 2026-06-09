<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: graduation_years
 * Skema sesuai 02_DATABASE.md §2.2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_years', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('year');
            $table->string('academic_year', 20);
            $table->enum('semester', ['Ganjil', 'Genap']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // UNIQUE (year, semester) sesuai 02_DATABASE.md
            $table->unique(['year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_years');
    }
};
