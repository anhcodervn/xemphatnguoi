<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xóa toàn bộ schema của hệ thống gói dịch vụ cũ.
     *
     * Các cột tham chiếu bên ngoài được xóa trước, sau đó phá vòng khóa ngoại
     * giữa đơn gói và subscription rồi mới xóa các bảng dữ liệu chính.
     */
    public function up(): void
    {
        if (Schema::hasTable('api_keys') && Schema::hasColumn('api_keys', 'user_subscription_id')) {
            Schema::table('api_keys', function (Blueprint $table): void {
                $table->dropColumn('user_subscription_id');
            });
        }

        if (Schema::hasTable('coupon_logs') && Schema::hasColumn('coupon_logs', 'package_order_id')) {
            Schema::table('coupon_logs', function (Blueprint $table): void {
                $table->dropColumn('package_order_id');
            });
        }

        if (Schema::hasTable('coupons') && Schema::hasColumn('coupons', 'applicable_package_ids')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->dropColumn('applicable_package_ids');
            });
        }

        Schema::dropIfExists('accounts');
        Schema::dropIfExists('extra_account_orders');

        if (Schema::hasTable('package_orders') && Schema::hasColumn('package_orders', 'source_subscription_id')) {
            Schema::table('package_orders', function (Blueprint $table): void {
                $table->dropForeign(['source_subscription_id']);
            });
        }

        if (Schema::hasTable('user_subscriptions') && Schema::hasColumn('user_subscriptions', 'order_id')) {
            Schema::table('user_subscriptions', function (Blueprint $table): void {
                $table->dropForeign(['order_id']);
            });
        }

        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('package_orders');
        Schema::dropIfExists('user_packages');
        Schema::dropIfExists('packages');
    }

    /**
     * Không thể phục hồi dữ liệu gói đã bị xóa bằng rollback migration.
     */
    public function down(): void
    {
        throw new RuntimeException('Không thể khôi phục hệ thống gói dịch vụ cũ sau khi dữ liệu đã bị xóa.');
    }
};
