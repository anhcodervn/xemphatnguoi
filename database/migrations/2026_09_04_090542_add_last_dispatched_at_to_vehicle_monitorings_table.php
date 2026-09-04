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
        Schema::table('vehicle_monitorings', function (Blueprint $table): void {
            $table->timestamp('last_dispatched_at')->nullable()->after('last_checked_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_monitorings', function (Blueprint $table): void {
            $table->dropIndex(['last_dispatched_at']);
            $table->dropColumn('last_dispatched_at');
        });
    }
};
