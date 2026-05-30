<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_account_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('price', 20, 2);
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'cancelled', 'expired'])->default('pending');
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['user_subscription_id', 'status']);
            $table->index('expired_at');
            $table->comment('Orders for purchasing extra account slot quota on top of a subscription.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_account_orders');
    }
};
