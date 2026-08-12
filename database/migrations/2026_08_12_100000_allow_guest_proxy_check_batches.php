<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proxy_check_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::whenTableDoesntHaveColumn('proxy_check_batches', 'guest_session_hash', function (Blueprint $table) {
            $table->char('guest_session_hash', 64)->nullable()->after('user_id');
        });

        Schema::whenTableDoesntHaveIndex(
            'proxy_check_batches',
            'proxy_checks_guest_type_created_idx',
            function (Blueprint $table) {
                $table->index(
                    ['guest_session_hash', 'check_type', 'created_at'],
                    'proxy_checks_guest_type_created_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::whenTableHasIndex(
            'proxy_check_batches',
            'proxy_checks_guest_type_created_idx',
            function (Blueprint $table) {
                $table->dropIndex('proxy_checks_guest_type_created_idx');
            },
        );

        Schema::whenTableHasColumn('proxy_check_batches', 'guest_session_hash', function (Blueprint $table) {
            $table->dropColumn('guest_session_hash');
        });
    }
};
