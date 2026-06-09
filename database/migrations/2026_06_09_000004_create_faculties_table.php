<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: faculties
 * Skema sesuai 02_DATABASE.md §2.2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->string('dean_name', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};
