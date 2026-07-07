<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            if (! Schema::hasColumn('api_keys', 'key_type')) {
                $table->string('key_type', 20)->default('wallet')->after('user_id');
                $table->index(['user_id', 'key_type'], 'api_keys_user_id_key_type_index');
            }

            if (! Schema::hasColumn('api_keys', 'user_subscription_id')) {
                $table->foreignId('user_subscription_id')
                    ->nullable()
                    ->after('key_type')
                    ->constrained('user_subscriptions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            if (Schema::hasColumn('api_keys', 'user_subscription_id')) {
                $table->dropConstrainedForeignId('user_subscription_id');
            }

            if (Schema::hasColumn('api_keys', 'key_type')) {
                $table->dropIndex('api_keys_user_id_key_type_index');
                $table->dropColumn('key_type');
            }
        });
    }
};
