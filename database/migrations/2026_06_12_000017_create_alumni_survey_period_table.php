<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_survey_period', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->foreignId('survey_period_id')->constrained('survey_periods')->cascadeOnDelete();
            $table->timestamp('invitation_sent_at')->nullable();
            $table->enum('invitation_channel', ['email', 'whatsapp', 'both'])->nullable();
            $table->tinyInteger('reminder_count')->unsigned()->default(0);
            $table->timestamp('last_reminder_at')->nullable();

            // Satu alumni hanya boleh terdaftar sekali per periode
            $table->unique(['alumni_id', 'survey_period_id']);
            $table->index('survey_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_survey_period');
    }
};
