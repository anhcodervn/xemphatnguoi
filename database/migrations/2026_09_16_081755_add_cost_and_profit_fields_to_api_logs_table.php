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
        Schema::table('api_logs', function (Blueprint $table): void {
            $table->decimal('provider_cost', 12, 2)->default(0)->after('charged_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_logs', function (Blueprint $table): void {
            $table->dropColumn('provider_cost');
        });
    }
};
