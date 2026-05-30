<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->unsignedInteger('account_limit')->default(0)->after('duration_days');
            $table->boolean('can_buy_extra_account')->default(false)->after('account_limit');
            $table->decimal('extra_account_price', 20, 2)->default(0)->after('can_buy_extra_account');
        });

        DB::table('packages')->update([
            'account_limit' => DB::raw('concurrent_limit'),
        ]);
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn([
                'account_limit',
                'can_buy_extra_account',
                'extra_account_price',
            ]);
        });
    }
};
