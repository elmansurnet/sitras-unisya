<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_employer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained('employers')->cascadeOnDelete();
            $table->tinyInteger('is_verified')->default(0)->comment('Diverifikasi admin: 1=relasi sah');
            $table->timestamp('created_at')->nullable();

            $table->unique(['alumni_id', 'employer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_employer');
    }
};
