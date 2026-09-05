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
        Schema::table('traffic_fine_results', function (Blueprint $table) {
            $table->dropUnique('traffic_fine_plate_vehicle_unique');
            $table->unique(['provider', 'plate', 'vehicle_type'], 'traffic_fine_provider_plate_vehicle_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traffic_fine_results', function (Blueprint $table) {
            $table->dropUnique('traffic_fine_provider_plate_vehicle_unique');
            $table->unique(['plate', 'vehicle_type'], 'traffic_fine_plate_vehicle_unique');
        });
    }
};
