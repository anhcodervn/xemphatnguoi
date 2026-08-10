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
        Schema::table('user_proxies', function (Blueprint $table): void {
            $table->text('provider_code')->nullable()->after('provider_proxy_id');
            $table->longText('response')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_proxies', function (Blueprint $table): void {
            $table->dropColumn(['provider_code', 'response']);
        });
    }
};
