<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 20, 2);
            $table->text('description')->nullable();
            $table->timestamp('transaction_time')->nullable();
            $table->enum('type', ['credit', 'debit'])->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['bank_account_id', 'transaction_time']);
            $table->comment('Normalized transactions synchronized from banks.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
