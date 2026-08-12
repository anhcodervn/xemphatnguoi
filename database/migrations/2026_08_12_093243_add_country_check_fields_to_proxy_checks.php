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
        Schema::table('proxy_check_batches', function (Blueprint $table) {
            $table->string('check_type', 20)->default('live')->after('user_id');

            $table->index(['user_id', 'check_type', 'created_at']);
        });

        Schema::table('proxy_check_items', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('exit_ip');
            $table->string('country_name', 100)->nullable()->after('country_code');
            $table->string('region_name', 150)->nullable()->after('country_name');
            $table->string('city_name', 150)->nullable()->after('region_name');
            $table->string('timezone', 100)->nullable()->after('city_name');
            $table->string('isp', 180)->nullable()->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxy_check_items', function (Blueprint $table) {
            $table->dropColumn([
                'country_code',
                'country_name',
                'region_name',
                'city_name',
                'timezone',
                'isp',
            ]);
        });

        Schema::table('proxy_check_batches', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'check_type', 'created_at']);
            $table->dropColumn('check_type');
        });
    }
};
