<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proxy_products', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('default_provider_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('proxy_products', function (Blueprint $table): void {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
