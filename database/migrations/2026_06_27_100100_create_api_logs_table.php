<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->constrained('api_keys')->cascadeOnDelete();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->string('ip', 45)->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['api_key_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
