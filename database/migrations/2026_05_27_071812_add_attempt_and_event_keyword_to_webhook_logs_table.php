<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table): void {
            $table->string('event_keyword')->nullable()->after('webhook_id');
            $table->unsignedTinyInteger('attempt')->default(1)->after('status_code');
            $table->index(['webhook_id', 'event_keyword'], 'webhook_logs_webhook_event_index');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table): void {
            $table->dropIndex('webhook_logs_webhook_event_index');
            $table->dropColumn(['event_keyword', 'attempt']);
        });
    }
};
