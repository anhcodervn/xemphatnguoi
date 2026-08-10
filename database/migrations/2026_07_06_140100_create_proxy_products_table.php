<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_products', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('country_code', 2)->nullable()->index();
            $table->string('protocol', 20)->default('http')->index();
            $table->text('description')->nullable();
            $table->string('provider_product_code')->nullable();
            $table->foreignId('default_provider_id')->nullable()->constrained('proxy_providers')->nullOnDelete();
            $table->decimal('base_price', 12, 4)->default(0);
            $table->decimal('selling_price', 12, 4)->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('max_quantity')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_products');
    }
};
