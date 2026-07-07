<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            if (! Schema::hasColumn('packages', 'package_limits')) {
                $table->json('package_limits')->nullable()->after('features');
            }
        });

        Schema::table('user_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_subscriptions', 'package_limits')) {
                $table->json('package_limits')->nullable()->after('package_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            if (Schema::hasColumn('packages', 'package_limits')) {
                $table->dropColumn('package_limits');
            }
        });

        Schema::table('user_subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('user_subscriptions', 'package_limits')) {
                $table->dropColumn('package_limits');
            }
        });
    }
};
