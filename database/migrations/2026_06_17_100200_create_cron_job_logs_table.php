<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_job_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cron_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('run_uuid')->index();
            $table->unsignedInteger('attempt')->default(1);
            $table->enum('status', ['success', 'failed', 'timeout', 'error', 'blocked'])->index();
            $table->string('method', 10);
            $table->text('url');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('request_headers')->nullable();
            $table->text('request_body_preview')->nullable();
            $table->json('response_headers')->nullable();
            $table->mediumText('response_body_preview')->nullable();
            $table->unsignedInteger('response_size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_resolved')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['cron_job_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_job_logs');
    }
};
