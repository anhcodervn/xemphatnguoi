<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name')->nullable()->after('bank_account_id');
            $table->index(['user_id', 'bank_account_id', 'status'], 'webhooks_user_bank_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->dropIndex('webhooks_user_bank_status_index');
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('name');
        });
    }
};
