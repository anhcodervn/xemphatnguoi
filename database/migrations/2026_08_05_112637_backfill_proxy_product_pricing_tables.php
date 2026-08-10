<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('proxy_product_durations') || ! Schema::hasTable('proxy_product_price_tiers')) {
            return;
        }

        DB::table('proxy_products')
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $durationId = DB::table('proxy_product_durations')->insertGetId([
                        'proxy_product_id' => $product->id,
                        'duration_days' => $product->duration_days,
                        'provider_product_code' => $product->provider_product_code,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('proxy_product_price_tiers')->insert([
                        'proxy_product_duration_id' => $durationId,
                        'min_quantity' => $product->min_quantity,
                        'base_price' => $product->base_price,
                        'selling_price' => $product->selling_price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backfilled commercial data is intentionally retained; schema migrations own rollback.
    }
};
