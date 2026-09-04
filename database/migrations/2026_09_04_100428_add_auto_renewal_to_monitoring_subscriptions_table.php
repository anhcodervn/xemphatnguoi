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
            $table->boolean('auto_renew')->default(false)->after('status');
            $table->foreignId('renewed_from_subscription_id')
                ->nullable()
                ->after('auto_renew')
                ->unique()
                ->constrained('monitoring_subscriptions')
                ->nullOnDelete();
            $table->unsignedInteger('renewal_count')->default(0)->after('renewed_from_subscription_id');
            $table->timestamp('last_renewed_at')->nullable()->after('expires_at');
            $table->index(['auto_renew', 'status', 'expires_at'], 'monitoring_subscriptions_auto_renew_due_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_subscriptions', function (Blueprint $table) {
            $table->dropIndex('monitoring_subscriptions_auto_renew_due_index');
            $table->dropConstrainedForeignId('renewed_from_subscription_id');
            $table->dropColumn(['auto_renew', 'renewal_count', 'last_renewed_at']);
        });
    }
};
