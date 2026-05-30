<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_orders', function (Blueprint $table): void {
            $table->foreignId('source_subscription_id')
                ->nullable()
                ->after('package_id')
                ->constrained('user_subscriptions')
                ->nullOnDelete();
            $table->decimal('credit_amount', 20, 2)->default(0)->after('discount_amount');
            $table->timestamp('expires_at')->nullable()->after('paid_at');

            $table->index(['user_id', 'payment_status', 'expires_at'], 'package_orders_user_pending_expired_idx');
        });
    }

    public function down(): void
    {
        Schema::table('package_orders', function (Blueprint $table): void {
            $table->dropIndex('package_orders_user_pending_expired_idx');
            $table->dropConstrainedForeignId('source_subscription_id');
            $table->dropColumn(['credit_amount', 'expires_at']);
        });
    }
};
