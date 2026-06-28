<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_job_alert_channel', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cron_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cron_alert_channel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cron_job_id', 'cron_alert_channel_id'], 'cron_job_alert_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_job_alert_channel');
    }
};
