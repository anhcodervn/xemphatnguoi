<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            foreach ([
                'proxy_check_items',
                'proxy_check_batches',
                'proxy_product_price_tiers',
                'proxy_product_durations',
                'user_proxies',
                'proxy_orders',
                'proxy_products',
                'proxy_services',
                'proxy_categories',
                'proxy_providers',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        });
    }

    /**
     * Obsolete marketplace data cannot be reconstructed safely.
     */
    public function down(): void {}
};
