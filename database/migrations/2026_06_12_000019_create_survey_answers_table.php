<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained('survey_responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->text('answer_text')->nullable();
            // Array of question_option IDs (untuk radio, checkbox)
            $table->json('answer_options')->nullable();
            $table->string('answer_value', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->timestamps();

            $table->index('survey_response_id');
            $table->index('question_id');
            // Satu jawaban per pertanyaan per respons
            $table->unique(['survey_response_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
