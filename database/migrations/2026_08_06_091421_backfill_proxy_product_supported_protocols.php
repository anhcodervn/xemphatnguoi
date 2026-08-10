<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allowedProtocols = ['http', 'https', 'socks4', 'socks5'];

        DB::table('proxy_products')
            ->whereNull('supported_protocols')
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($allowedProtocols): void {
                foreach ($products as $product) {
                    $protocol = strtolower((string) $product->protocol);
                    $protocol = in_array($protocol, $allowedProtocols, true) ? $protocol : 'http';

                    DB::table('proxy_products')
                        ->where('id', $product->id)
                        ->update(['supported_protocols' => json_encode([$protocol], JSON_THROW_ON_ERROR)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backfilled protocol selections are retained until the schema migration removes the column.
    }
};
