<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_recharge', function (Blueprint $table): void {
            if (! Schema::hasColumn('config_recharge', 'provider')) {
                $table->string('provider', 40)->default('manual')->after('id');
            }

            if (! Schema::hasColumn('config_recharge', 'api_base_url')) {
                $table->string('api_base_url')->nullable()->after('transfer_prefix');
            }

            if (! Schema::hasColumn('config_recharge', 'api_key')) {
                $table->string('api_key', 120)->nullable()->after('api_base_url');
            }

            if (! Schema::hasColumn('config_recharge', 'api_secret')) {
                $table->text('api_secret')->nullable()->after('api_key');
            }

            if (! Schema::hasColumn('config_recharge', 'api_bank_id')) {
                $table->unsignedBigInteger('api_bank_id')->nullable()->after('api_secret');
            }
        });
    }

    public function down(): void
    {
        Schema::table('config_recharge', function (Blueprint $table): void {
            $columns = [
                'provider',
                'api_base_url',
                'api_key',
                'api_secret',
                'api_bank_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('config_recharge', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
