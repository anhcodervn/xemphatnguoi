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
        DB::table('proxy_products')
            ->whereNotNull('proxy_service_id')
            ->orderBy('id')
            ->eachById(function (object $product): void {
                $categoryId = DB::table('proxy_services')
                    ->where('id', $product->proxy_service_id)
                    ->value('proxy_category_id');

                if ($categoryId !== null) {
                    DB::table('proxy_products')->where('id', $product->id)->update([
                        'proxy_category_id' => $categoryId,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('proxy_products')->update(['proxy_category_id' => null]);
    }
};
