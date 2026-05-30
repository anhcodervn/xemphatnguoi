<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('order_code')->unique();
            $table->decimal('price', 20, 2);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('final_amount', 20, 2);
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('order_code');
            $table->index(['user_id', 'status']);
            $table->index(['package_id', 'payment_status']);
            $table->comment('Package purchase orders and payment lifecycle.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_orders');
    }
};
