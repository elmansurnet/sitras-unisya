<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            $table->foreignId('section_id')
                  ->nullable()
                  ->constrained('questionnaire_sections')
                  ->nullOnDelete();
            $table->text('question_text');
            $table->enum('question_type', [
                'text',
                'textarea',
                'radio',
                'checkbox',
                'select',
                'likert',
                'rating',
                'date',
                'file',
                'number',
            ]);
            $table->tinyInteger('is_required')->default(1);
            $table->smallInteger('order_number')->unsigned();
            $table->text('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('conditional_logic')->nullable();
            $table->timestamps();

            $table->index('questionnaire_id');
            $table->index('section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
