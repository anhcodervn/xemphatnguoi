<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proxy_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('proxy_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('target_user_proxy_id')->nullable();
            $table->string('order_code', 40)->unique();
            $table->string('idempotency_key', 100);
            $table->enum('type', ['purchase', 'change', 'renew'])->default('purchase');
            $table->enum('status', ['pending', 'processing', 'fulfilled', 'failed', 'refunded'])->default('pending');
            $table->string('product_code');
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('duration_days')->default(1);
            $table->string('country_code', 2)->nullable();
            $table->string('protocol', 20);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('total_amount', 20, 4);
            $table->text('external_order_id')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key'], 'proxy_orders_user_idempotency_unique');
            $table->index(['user_id', 'status', 'created_at'], 'proxy_orders_user_status_created_index');
            $table->index(['user_id', 'type', 'created_at'], 'proxy_orders_user_type_created_index');
            $table->index(['proxy_product_id', 'status'], 'proxy_orders_product_status_index');
            $table->index(['target_user_proxy_id', 'status'], 'proxy_orders_target_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_orders');
    }
};
