<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_orders', function (Blueprint $table): void {
            $table->boolean('auto_renew_enabled')->default(false)->after('payment_method');
        });

        Schema::table('user_subscriptions', function (Blueprint $table): void {
            $table->boolean('auto_renew_enabled')->default(false)->after('used_account');
            $table->timestamp('auto_renew_attempted_at')->nullable()->after('expires_at');
            $table->string('auto_renew_status', 20)->nullable()->after('auto_renew_attempted_at');
            $table->text('auto_renew_message')->nullable()->after('auto_renew_status');

            $table->index(['auto_renew_enabled', 'expires_at'], 'user_subscriptions_auto_renew_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table): void {
            $table->dropIndex('user_subscriptions_auto_renew_due_idx');
            $table->dropColumn([
                'auto_renew_enabled',
                'auto_renew_attempted_at',
                'auto_renew_status',
                'auto_renew_message',
            ]);
        });

        Schema::table('package_orders', function (Blueprint $table): void {
            $table->dropColumn('auto_renew_enabled');
        });
    }
};
