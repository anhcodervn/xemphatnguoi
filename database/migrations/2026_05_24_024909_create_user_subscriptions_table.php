<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('package_orders')->nullOnDelete();
            $table->string('package_name');
            $table->decimal('package_price', 20, 2);
            $table->unsignedInteger('base_account_limit')->default(0);
            $table->unsignedInteger('extra_account_limit')->default(0);
            $table->unsignedInteger('used_account')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'expires_at']);
            $table->index(['package_id', 'status']);
            $table->comment('Source of truth for active package quota and subscription lifecycle.');
        });

        $legacySubscriptions = DB::table('user_packages')
            ->join('packages', 'packages.id', '=', 'user_packages.package_id')
            ->select([
                'user_packages.user_id',
                'user_packages.package_id',
                DB::raw('NULL as order_id'),
                'packages.name as package_name',
                'packages.price as package_price',
                'packages.account_limit as base_account_limit',
                DB::raw('0 as extra_account_limit'),
                DB::raw('0 as used_account'),
                'user_packages.start_at as starts_at',
                'user_packages.expired_at as expires_at',
                'user_packages.status',
                'user_packages.created_at',
                'user_packages.updated_at',
            ])
            ->get()
            ->map(fn (object $subscription): array => (array) $subscription)
            ->all();

        if ($legacySubscriptions !== []) {
            DB::table('user_subscriptions')->insert($legacySubscriptions);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
