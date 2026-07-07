<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_subscriptions', 'used_captcha_quota')) {
                $table->unsignedBigInteger('used_captcha_quota')->default(0)->after('used_account');
            }

            if (! Schema::hasColumn('user_subscriptions', 'captcha_usage_by_service')) {
                $table->json('captcha_usage_by_service')->nullable()->after('used_captcha_quota');
            }
        });

        Schema::table('captcha_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('captcha_tasks', 'billing_source')) {
                $table->string('billing_source', 20)->default('wallet')->after('selling_price');
            }

            if (! Schema::hasColumn('captcha_tasks', 'package_subscription_id')) {
                $table->foreignId('package_subscription_id')->nullable()->after('billing_source')->constrained('user_subscriptions')->nullOnDelete();
            }

            if (! Schema::hasColumn('captcha_tasks', 'package_quota_consumed')) {
                $table->unsignedInteger('package_quota_consumed')->default(0)->after('package_subscription_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('captcha_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('captcha_tasks', 'package_quota_consumed')) {
                $table->dropColumn('package_quota_consumed');
            }

            if (Schema::hasColumn('captcha_tasks', 'package_subscription_id')) {
                $table->dropConstrainedForeignId('package_subscription_id');
            }

            if (Schema::hasColumn('captcha_tasks', 'billing_source')) {
                $table->dropColumn('billing_source');
            }
        });

        Schema::table('user_subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('user_subscriptions', 'captcha_usage_by_service')) {
                $table->dropColumn('captcha_usage_by_service');
            }

            if (Schema::hasColumn('user_subscriptions', 'used_captcha_quota')) {
                $table->dropColumn('used_captcha_quota');
            }
        });
    }
};
