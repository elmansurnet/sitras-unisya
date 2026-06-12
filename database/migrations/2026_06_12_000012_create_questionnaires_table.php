<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['alumni', 'employer']);
            $table->smallInteger('version')->unsigned()->default(1);
            $table->enum('status', ['draft', 'aktif', 'arsip'])->default('draft');
            $table->tinyInteger('is_paginated')->default(0);
            $table->tinyInteger('estimated_minutes')->unsigned()->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};
