<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('type', ['email', 'whatsapp']);
            $table->string('event', 100);
            // Subject hanya untuk email, NULL untuk whatsapp
            $table->string('subject', 255)->nullable();
            $table->text('body');
            // Daftar variabel yang tersedia beserta deskripsinya
            $table->json('variables')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->index('type');
            $table->index('event');
            // Satu template per kombinasi type+event
            $table->unique(['type', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
