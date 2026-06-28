<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['user_id']);
            }

            $table->dropUnique('wallets_user_id_unique');
            $table->string('type')->default('main')->after('user_id');
            $table->unique(['user_id', 'type']);
            $table->index('type');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['user_id']);
            }

            $table->dropUnique('wallets_user_id_type_unique');
            $table->dropIndex(['type']);
            $table->dropColumn('type');
            $table->unique('user_id');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
        });
    }
};
