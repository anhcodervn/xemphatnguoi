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
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->string('ip', 45)->nullable();
            $table->longText('request_data')->nullable();
            $table->longText('response_data')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('endpoint');
            $table->index('api_key_id');
            $table->index(['endpoint', 'created_at']);
            $table->index(['api_key_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->comment('High-volume API request and response logs.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
