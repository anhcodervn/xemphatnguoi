<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bank_code')->nullable();
            $table->string('account_number')->nullable();
            $table->string('transaction_code')->unique();
            $table->decimal('amount', 20, 2);
            $table->text('content')->nullable();
            $table->json('raw_data')->nullable();
            $table->enum('status', ['pending', 'matched', 'success', 'failed', 'cancelled'])->default('pending')->index();
            $table->timestamps();

            $table->index('created_at');
            $table->index('transaction_code');
            $table->index(['user_id', 'status']);
            $table->comment('Incoming and matched payment transactions.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
