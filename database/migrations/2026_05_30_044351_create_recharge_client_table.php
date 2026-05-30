<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recharge_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recharge_method_id')->nullable()->constrained('recharge_methods')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('matched_bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();
            $table->string('order_code', 40)->unique();
            $table->string('client_order_code', 100)->nullable();
            $table->string('method', 50)->nullable();
            $table->string('method_label', 255)->nullable();
            $table->decimal('amount', 20, 2);
            $table->string('bank_name', 255)->nullable();
            $table->string('account_number', 255)->nullable();
            $table->string('account_name', 255)->nullable();
            $table->string('transfer_content', 100)->unique();
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled', 'expired'])->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_order_code']);
            $table->index(['bank_account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recharge_client');
    }
};
