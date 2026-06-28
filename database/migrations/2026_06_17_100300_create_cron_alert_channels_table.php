<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_alert_channels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cron_job_id')->nullable()->constrained('cron_jobs')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['discord', 'telegram', 'webhook', 'email']);
            $table->text('target_url')->nullable();
            $table->text('telegram_bot_token')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('email')->nullable();
            $table->json('events')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_alert_channels');
    }
};
