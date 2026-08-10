<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proxy_providers', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique()->after('name');
            $table->string('order_method')->nullable()->index()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('proxy_providers', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropIndex(['order_method']);
            $table->dropColumn(['code', 'order_method']);
        });
    }
};
