<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('config_recharge') || Schema::hasColumn('config_recharge', 'webhook_secret')) {
            return;
        }

        Schema::table('config_recharge', function (Blueprint $table): void {
            $table->text('webhook_secret')->nullable()->after('api_secret');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('config_recharge') || ! Schema::hasColumn('config_recharge', 'webhook_secret')) {
            return;
        }

        Schema::table('config_recharge', function (Blueprint $table): void {
            $table->dropColumn('webhook_secret');
        });
    }
};
