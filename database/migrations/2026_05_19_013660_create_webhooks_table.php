<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('secret_key');
            $table->json('events')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->comment('Outbound webhook subscriptions for customer callbacks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
