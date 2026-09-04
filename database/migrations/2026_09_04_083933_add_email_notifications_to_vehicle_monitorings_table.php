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
            $table->boolean('email_notifications')->default(false)->after('enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_monitorings', function (Blueprint $table): void {
            $table->dropColumn('email_notifications');
        });
    }
};
