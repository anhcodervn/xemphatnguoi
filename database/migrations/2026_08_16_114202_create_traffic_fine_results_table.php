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
        Schema::create('traffic_fine_results', function (Blueprint $table): void {
            $table->id();
            $table->string('plate', 16);
            $table->string('vehicle_type', 32);
            $table->string('status', 32);
            $table->unsignedInteger('violation_count')->default(0);
            $table->json('response_json');
            $table->string('provider', 64);
            $table->timestamp('checked_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['plate', 'vehicle_type'], 'traffic_fine_plate_vehicle_unique');
            $table->index(['plate', 'vehicle_type', 'checked_at'], 'traffic_fine_lookup_index');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_fine_results');
    }
};
