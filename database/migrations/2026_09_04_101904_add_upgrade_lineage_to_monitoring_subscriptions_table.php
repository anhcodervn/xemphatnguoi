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
        Schema::table('monitoring_subscriptions', function (Blueprint $table) {
            $table->foreignId('upgraded_from_subscription_id')
                ->nullable()
                ->after('renewed_from_subscription_id')
                ->unique()
                ->constrained('monitoring_subscriptions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upgraded_from_subscription_id');
        });
    }
};
