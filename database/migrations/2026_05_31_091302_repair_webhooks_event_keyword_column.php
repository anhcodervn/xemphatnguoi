<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('webhooks', 'event_keyword')) {
            Schema::table('webhooks', function (Blueprint $table): void {
                $table->string('event_keyword')->nullable()->after('secret_key');
            });
        }

        if (Schema::hasColumn('webhooks', 'events')) {
            DB::table('webhooks')
                ->select(['id', 'events', 'event_keyword'])
                ->orderBy('id')
                ->lazy()
                ->each(function (object $webhook): void {
                    if ($webhook->event_keyword !== null && trim((string) $webhook->event_keyword) !== '') {
                        return;
                    }

                    $eventKeyword = null;
                    $events = json_decode((string) ($webhook->events ?? 'null'), true);

                    if (is_array($events) && array_key_exists(0, $events)) {
                        $firstEvent = trim((string) $events[0]);
                        $eventKeyword = $firstEvent !== '' ? $firstEvent : null;
                    }

                    DB::table('webhooks')
                        ->where('id', $webhook->id)
                        ->update([
                            'event_keyword' => $eventKeyword,
                            'updated_at' => now(),
                        ]);
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('webhooks', 'event_keyword')) {
            Schema::table('webhooks', function (Blueprint $table): void {
                $table->dropColumn('event_keyword');
            });
        }
    }
};
