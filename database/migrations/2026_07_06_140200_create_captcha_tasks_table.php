<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captcha_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('captcha_service_id')->constrained('captcha_services')->cascadeOnDelete();
            $table->foreignId('captcha_source_id')->nullable()->constrained('captcha_sources')->nullOnDelete();
            $table->string('task_code')->unique();
            $table->string('external_task_id')->nullable();
            $table->string('service_code');
            $table->string('status')->default('pending');
            $table->json('request_payload');
            $table->json('result_payload')->nullable();
            $table->decimal('provider_cost', 12, 4)->default(0);
            $table->decimal('selling_price', 12, 4)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('solved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['api_key_id', 'status']);
            $table->index(['service_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captcha_tasks');
    }
};
