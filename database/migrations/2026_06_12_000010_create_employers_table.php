<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company_name');
            $table->enum('company_type', ['swasta', 'bumn', 'pemerintah', 'ngo', 'startup', 'lainnya'])->nullable();
            $table->string('industry_sector')->nullable();
            $table->enum('company_scale', ['mikro', 'kecil', 'menengah', 'besar', 'multinasional'])->nullable();
            $table->text('address_street')->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_province', 100)->nullable();
            $table->string('address_country', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_position', 100)->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('contact_person_phone', 20)->nullable();
            $table->enum('survey_status', ['belum_disurvei', 'terkirim', 'selesai'])->default('belum_disurvei');
            $table->string('survey_token', 64)->unique()->nullable();
            $table->timestamp('survey_token_expires_at')->nullable();
            $table->timestamp('survey_token_used_at')->nullable();
            $table->string('logo')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('survey_status');
            $table->index('company_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
