<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->restrictOnDelete();
            $table->foreignId('survey_period_id')->nullable()->constrained('survey_periods')->nullOnDelete();
            $table->enum('respondent_type', ['alumni', 'employer']);
            // Salah satu wajib diisi sesuai respondent_type
            $table->foreignId('alumni_id')->nullable()->constrained('alumni')->cascadeOnDelete();
            $table->foreignId('employer_id')->nullable()->constrained('employers')->cascadeOnDelete();
            $table->enum('status', ['draft', 'selesai'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->tinyInteger('completion_percentage')->unsigned()->default(0);
            $table->timestamps();

            $table->index('questionnaire_id');
            $table->index('survey_period_id');
            $table->index('alumni_id');
            $table->index('employer_id');
            $table->index('status');
            $table->index('respondent_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
