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
        Schema::table('coupons', function (Blueprint $table): void {
            $table->string('name')->after('code');
            $table->text('description')->nullable()->after('name');
            $table->decimal('min_order_amount', 20, 2)->default(0)->after('value');
            $table->decimal('max_discount_amount', 20, 2)->nullable()->after('min_order_amount');
            $table->unsignedInteger('max_usage_per_user')->nullable()->after('max_usage');
            $table->timestamp('starts_at')->nullable()->after('used_count');
            $table->boolean('first_order_only')->default(false)->after('expired_at');
            $table->boolean('is_active')->default(true)->after('first_order_only');
            $table->json('applicable_package_ids')->nullable()->after('is_active');
            $table->json('requirements')->nullable()->after('applicable_package_ids');
            $table->softDeletes()->after('updated_at');

            $table->index(['is_active', 'expired_at'], 'coupons_active_expired_idx');
            $table->index(['type', 'is_active'], 'coupons_type_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropIndex('coupons_active_expired_idx');
            $table->dropIndex('coupons_type_active_idx');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'name',
                'description',
                'min_order_amount',
                'max_discount_amount',
                'max_usage_per_user',
                'starts_at',
                'first_order_only',
                'is_active',
                'applicable_package_ids',
                'requirements',
            ]);
        });
    }
};
