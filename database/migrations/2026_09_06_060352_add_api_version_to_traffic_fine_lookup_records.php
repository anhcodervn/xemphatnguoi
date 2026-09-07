<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('traffic_fine_results', function (Blueprint $table): void {
            $table->dropUnique('traffic_fine_provider_plate_vehicle_unique');
            $table->string('api_version', 8)->default('v1')->after('provider');
            $table->unique(
                ['api_version', 'provider', 'plate', 'vehicle_type'],
                'traffic_fine_version_provider_plate_vehicle_unique',
            );
        });

        Schema::table('traffic_fine_lookup_logs', function (Blueprint $table): void {
            $table->string('api_version', 8)->default('v1')->after('vehicle_type');
        });

        Schema::table('lookup_histories', function (Blueprint $table): void {
            $table->string('api_version', 8)->default('v1')->after('vehicle_type');
        });

        DB::table('traffic_fine_results')
            ->where('provider', 'xephatnguoi_v2')
            ->update(['api_version' => 'v2']);

        DB::table('traffic_fine_lookup_logs')
            ->where('provider', 'xephatnguoi_v2')
            ->update(['api_version' => 'v2']);

        DB::table('lookup_histories')
            ->whereIn(
                'traffic_fine_result_id',
                DB::table('traffic_fine_results')
                    ->select('id')
                    ->where('api_version', 'v2'),
            )
            ->update(['api_version' => 'v2']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lookup_histories', function (Blueprint $table): void {
            $table->dropColumn('api_version');
        });

        Schema::table('traffic_fine_lookup_logs', function (Blueprint $table): void {
            $table->dropColumn('api_version');
        });

        Schema::table('traffic_fine_results', function (Blueprint $table): void {
            $table->dropUnique('traffic_fine_version_provider_plate_vehicle_unique');
            $table->dropColumn('api_version');
            $table->unique(['provider', 'plate', 'vehicle_type'], 'traffic_fine_provider_plate_vehicle_unique');
        });
    }
};
