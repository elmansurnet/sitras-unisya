<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->smallInteger('year')->unsigned();
            $table->date('start_date');
            $table->date('end_date');
            // JSON array of graduation_year IDs — no FK by design (flexibility)
            $table->json('target_graduation_years')->nullable();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('year');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_periods');
    }
};
