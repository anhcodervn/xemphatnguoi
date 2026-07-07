<?php

namespace App\Console\Commands;

use App\Utils\SendMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiscordServerHeartbeatCommand extends Command
{
    protected $signature = 'monitor:discord-heartbeat
        {--channel=ops : queue|info|ops|security|alerts|recovered|staging}
        {--title=Server heartbeat : Tiêu đề gửi lên Discord}
        {--note= : Ghi chú bổ sung}';

    protected $description = 'Gửi heartbeat theo dõi server lên Discord webhook.';

    public function handle(): int
    {
        $channel = trim((string) $this->option('channel'));
        $title = trim((string) $this->option('title')) ?: 'Server heartbeat';
        $note = trim((string) $this->option('note'));

        $payload = [
            'Thời gian app' => now()->format('Y-m-d H:i:s'),
            'PHP' => PHP_VERSION,
            'Queue driver' => (string) config('queue.default'),
            'Cache store' => (string) config('cache.default'),
            'DB connection' => (string) config('database.default'),
            'Database ping' => $this->databasePing(),
            'Disk free' => $this->formatBytes(@disk_free_space(base_path())),
        ];

        if ($note !== '') {
            $payload['Ghi chú'] = $note;
        }

        $this->sendToChannel($channel, $title, $payload);

        $this->info(sprintf('Đã gửi heartbeat Discord tới channel [%s].', $channel));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendToChannel(string $channel, string $title, array $payload): void
    {
        match ($channel) {
            'queue' => SendMessage::sendQueueReport($title, $payload),
            'info' => SendMessage::sendInfoReport($title, $payload),
            'ops' => SendMessage::sendOpsReport($title, $payload),
            'security' => SendMessage::sendSecurityReport($title, $payload),
            'alerts' => SendMessage::sendAlertReport($title, $payload),
            'recovered' => SendMessage::sendRecoveredReport($title, $payload),
            'staging' => SendMessage::sendStagingReport($title, $payload),
            default => $this->fail(sprintf('Channel Discord [%s] không hợp lệ.', $channel)),
        };
    }

    private function databasePing(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (\Throwable $exception) {
            report($exception);

            return 'failed';
        }
    }

    private function formatBytes(float|int|false $bytes): string
    {
        if (! is_numeric($bytes) || $bytes < 0) {
            return '--';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 2).' '.$units[$unitIndex];
    }
}
