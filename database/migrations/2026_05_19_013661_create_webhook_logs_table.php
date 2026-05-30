<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->longText('payload')->nullable();
            $table->longText('response')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['webhook_id', 'created_at']);
            $table->index('status_code');
            $table->comment('Webhook delivery attempt logs.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
