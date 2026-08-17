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
        Schema::table('lookup_histories', function (Blueprint $table): void {
            $table->foreign('traffic_fine_result_id')
                ->references('id')
                ->on('traffic_fine_results')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lookup_histories', function (Blueprint $table): void {
            $table->dropForeign(['traffic_fine_result_id']);
        });
    }
};
