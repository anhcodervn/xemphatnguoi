<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recharge_orders', function (Blueprint $table): void {
            $table->foreignId('recharge_method_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('recharge_method_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['recharge_method_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('recharge_orders', function (Blueprint $table): void {
            $table->dropIndex(['recharge_method_id', 'status']);
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropConstrainedForeignId('recharge_method_id');
        });
    }
};
