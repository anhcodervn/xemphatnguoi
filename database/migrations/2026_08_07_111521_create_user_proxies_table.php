<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_proxies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proxy_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('proxy_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_order_id')->nullable()->constrained('proxy_orders')->nullOnDelete();
            $table->text('provider_proxy_id')->nullable();
            $table->string('label')->nullable();
            $table->enum('status', ['pending', 'active', 'changing', 'expired', 'disabled', 'error'])->default('pending');
            $table->string('country_code', 2)->nullable();
            $table->string('protocol', 20);
            $table->text('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_changed_at')->nullable();
            $table->timestamps();

            $table->index('proxy_provider_id', 'user_proxies_provider_id_index');
            $table->index(['user_id', 'status', 'expires_at'], 'user_proxies_user_status_expiry_index');
            $table->index(['user_id', 'proxy_product_id', 'created_at'], 'user_proxies_user_product_created_index');
            $table->index(['user_id', 'protocol', 'country_code'], 'user_proxies_user_protocol_country_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_proxies');
    }
};
