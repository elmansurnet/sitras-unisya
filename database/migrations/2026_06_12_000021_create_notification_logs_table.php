<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            // NULL jika template ad-hoc (tidak dari template tersimpan)
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->enum('type', ['email', 'whatsapp']);
            $table->string('recipient', 255);
            $table->string('recipient_type', 50)->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('body');
            // Status: delivered di ENUM untuk kompatibilitas masa depan (WA Gateway saat ini tidak mendukung webhook)
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            // Raw response dari WA Gateway atau SMTP, termasuk message_id jika tersedia
            $table->json('provider_response')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('recipient_id');
            $table->index('template_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
