<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->string('event_keyword')->nullable()->after('secret_key');
        });

        DB::table('webhooks')
            ->select(['id', 'events'])
            ->orderBy('id')
            ->lazy()
            ->each(function (object $webhook): void {
                $eventKeyword = null;
                $events = json_decode((string) ($webhook->events ?? 'null'), true);

                if (is_array($events) && array_key_exists(0, $events)) {
                    $firstEvent = $events[0];

                    if (is_string($firstEvent)) {
                        $firstEvent = trim($firstEvent);
                        $eventKeyword = $firstEvent !== '' ? $firstEvent : null;
                    }
                }

                DB::table('webhooks')
                    ->where('id', $webhook->id)
                    ->update([
                        'event_keyword' => $eventKeyword,
                    ]);
            });

        Schema::table('webhooks', function (Blueprint $table): void {
            $table->dropColumn('events');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->json('events')->nullable()->after('secret_key');
        });

        DB::table('webhooks')
            ->select(['id', 'event_keyword'])
            ->orderBy('id')
            ->lazy()
            ->each(function (object $webhook): void {
                $events = [$webhook->event_keyword ?? ''];

                DB::table('webhooks')
                    ->where('id', $webhook->id)
                    ->update([
                        'events' => json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
            });

        Schema::table('webhooks', function (Blueprint $table): void {
            $table->dropColumn('event_keyword');
        });
    }
};
