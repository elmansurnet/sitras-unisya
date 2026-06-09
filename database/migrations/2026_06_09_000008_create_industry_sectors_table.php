<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: industry_sectors
 * Skema sesuai 02_DATABASE.md §2.8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_sectors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('code', 20)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_sectors');
    }
};
