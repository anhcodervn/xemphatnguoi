<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cron_jobs', function (Blueprint $table): void {
            $table->string('group_name', 120)->nullable()->after('name');
            $table->index(['user_id', 'group_name']);
        });
    }

    public function down(): void
    {
        Schema::table('cron_jobs', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'group_name']);
            $table->dropColumn('group_name');
        });
    }
};
