<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('url');
            $table->enum('method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->default('GET');
            $table->json('headers')->nullable();
            $table->enum('body_type', ['none', 'json', 'form', 'raw'])->default('none');
            $table->longText('body')->nullable();
            $table->json('query_params')->nullable();
            $table->string('cron_expression')->nullable();
            $table->unsignedInteger('interval_seconds')->nullable();
            $table->string('timezone')->default('Asia/Ho_Chi_Minh');
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->unsignedInteger('connect_timeout_seconds')->default(5);
            $table->unsignedInteger('retry_count')->default(0);
            $table->unsignedInteger('retry_delay_seconds')->default(30);
            $table->unsignedInteger('max_response_size_kb')->default(20);
            $table->json('expected_status_codes')->nullable();
            $table->string('expected_body_contains')->nullable();
            $table->string('expected_body_not_contains')->nullable();
            $table->boolean('follow_redirects')->default(false);
            $table->boolean('verify_ssl')->default(true);
            $table->enum('status', ['active', 'paused', 'disabled'])->default('active')->index();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->enum('last_status', ['success', 'failed', 'timeout', 'error', 'blocked'])->nullable()->index();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->unsignedBigInteger('total_runs')->default(0);
            $table->unsignedBigInteger('total_success')->default(0);
            $table->unsignedBigInteger('total_failed')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_jobs');
    }
};
