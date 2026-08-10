<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proxy_orders', function (Blueprint $table): void {
            $table->foreign('target_user_proxy_id')
                ->references('id')
                ->on('user_proxies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proxy_orders', function (Blueprint $table): void {
            $table->dropForeign(['target_user_proxy_id']);
        });
    }
};
