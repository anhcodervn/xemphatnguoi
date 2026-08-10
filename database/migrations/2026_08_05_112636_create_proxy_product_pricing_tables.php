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
        Schema::create('proxy_product_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proxy_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('duration_days');
            $table->string('provider_product_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['proxy_product_id', 'duration_days'], 'proxy_durations_product_days_unique');
            $table->index(['proxy_product_id', 'sort_order'], 'proxy_durations_product_sort_index');
        });

        Schema::create('proxy_product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proxy_product_duration_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->decimal('base_price', 12, 4)->default(0);
            $table->decimal('selling_price', 12, 4);
            $table->timestamps();

            $table->unique(['proxy_product_duration_id', 'min_quantity'], 'proxy_price_tiers_duration_qty_unique');
            $table->index(['proxy_product_duration_id', 'min_quantity'], 'proxy_price_tiers_duration_qty_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxy_product_price_tiers');
        Schema::dropIfExists('proxy_product_durations');
    }
};
