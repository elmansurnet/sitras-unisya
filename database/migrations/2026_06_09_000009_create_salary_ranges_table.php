<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: salary_ranges
 * Skema sesuai 02_DATABASE.md §2.8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_ranges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('label', 100);
            $table->unsignedInteger('min_value')->nullable();
            $table->unsignedInteger('max_value')->nullable();
            $table->unsignedTinyInteger('order_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_ranges');
    }
};
