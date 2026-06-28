<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('config_recharge')) {
            return;
        }

        if (! Schema::hasColumn('config_recharge', 'qr_template')) {
            Schema::table('config_recharge', function (Blueprint $table): void {
                $table->text('qr_template')->nullable()->after('account_number');
            });
        }

        if (Schema::hasColumn('config_recharge', 'qr_link')) {
            DB::table('config_recharge')
                ->whereNull('qr_template')
                ->update([
                    'qr_template' => DB::raw('qr_link'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('config_recharge')) {
            return;
        }

        if (Schema::hasColumn('config_recharge', 'qr_template') && ! Schema::hasColumn('config_recharge', 'qr_link')) {
            Schema::table('config_recharge', function (Blueprint $table): void {
                $table->string('qr_link')->nullable()->after('account_number');
            });
        }

        if (Schema::hasColumn('config_recharge', 'qr_template') && Schema::hasColumn('config_recharge', 'qr_link')) {
            DB::table('config_recharge')
                ->whereNull('qr_link')
                ->update([
                    'qr_link' => DB::raw('qr_template'),
                ]);
        }
    }
};
