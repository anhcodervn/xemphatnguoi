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
        Schema::table('api_logs', function (Blueprint $table): void {
            $table->foreignId('wallet_transaction_id')
                ->nullable()
                ->after('api_key_id')
                ->constrained('wallet_transactions')
                ->nullOnDelete();
            $table->decimal('unit_price', 12, 2)->default(0)->after('response_time_ms');
            $table->decimal('charged_amount', 12, 2)->default(0)->after('unit_price');
            $table->string('billing_status', 32)->default('not_billable')->after('charged_amount');

            $table->index(['billing_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_logs', function (Blueprint $table): void {
            $table->dropIndex(['billing_status', 'created_at']);
            $table->dropConstrainedForeignId('wallet_transaction_id');
            $table->dropColumn(['unit_price', 'charged_amount', 'billing_status']);
        });
    }
};
