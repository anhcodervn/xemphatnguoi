<?php

namespace App\Features\Cron\Services;

use App\Models\CronAlertChannel;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Support\Enums\CronAlertChannelType;
use App\Support\MailQueue;
use App\Utils\SendMessage;
use Illuminate\Support\Facades\Http;
use Throwable;

class CronAlertService
{
    public function __construct(
        private readonly MailQueue $mailQueue,
    ) {}

    public function sendForEvent(CronJob $cronJob, CronJobLog $log, string $event): void
    {
        $cronJob->loadMissing('alertChannels', 'user');

        $this->sendProjectDiscordReport($cronJob, $log, $event);

        $channels = CronAlertChannel::query()
            ->where('user_id', $cronJob->user_id)
            ->where('is_enabled', true)
            ->get()
            ->filter(function (CronAlertChannel $channel) use ($cronJob, $event): bool {
                $events = is_array($channel->events) ? $channel->events : [];
                if (! in_array($event, $events, true)) {
                    return false;
                }

                if ($channel->cron_job_id !== null) {
                    return $channel->cron_job_id === $cronJob->id;
                }

                return $cronJob->alertChannels->contains('id', $channel->id);
            })
            ->values();

        foreach ($channels as $channel) {
            try {
                $this->sendChannelNotification($channel, $cronJob, $log, $event);
            } catch (Throwable) {
                // Swallow alert delivery failures to keep cron execution stable.
            }
        }
    }

    public function sendTest(CronAlertChannel $channel, CronJob $cronJob): void
    {
        $log = new CronJobLog([
            'status' => 'success',
            'method' => $cronJob->method->value,
            'url' => $cronJob->url,
            'started_at' => now(),
            'finished_at' => now(),
            'response_body_preview' => 'This is a test alert from AutoCron.',
        ]);

        $this->sendChannelNotification($channel, $cronJob, $log, 'on_fail');
    }

    private function sendChannelNotification(CronAlertChannel $channel, CronJob $cronJob, CronJobLog $log, string $event): void
    {
        $payload = [
            'product' => 'AutoCron',
            'event' => $event,
            'cron_job' => [
                'id' => $cronJob->id,
                'name' => $cronJob->name,
                'url' => $cronJob->url,
                'method' => $cronJob->method->value,
            ],
            'log' => [
                'status' => $log->status?->value ?? $log->status,
                'status_code' => $log->status_code,
                'duration_ms' => $log->duration_ms,
                'error_message' => $log->error_message,
                'response_preview' => $log->response_body_preview,
            ],
            'triggered_at' => now()->toIso8601String(),
        ];

        match ($channel->type) {
            CronAlertChannelType::Discord, CronAlertChannelType::Webhook => Http::timeout(10)->post(
                (string) $channel->target_url,
                $payload,
            ),
            CronAlertChannelType::Telegram => Http::timeout(10)->post(
                sprintf('https://api.telegram.org/bot%s/sendMessage', $channel->telegram_bot_token),
                [
                    'chat_id' => $channel->telegram_chat_id,
                    'text' => $this->telegramMessage($cronJob, $log, $event),
                ],
            ),
            CronAlertChannelType::Email => $this->mailQueue->dispatch(
                to: (string) $channel->email,
                subjectText: sprintf('[AutoCron] %s - %s', $event, $cronJob->name),
                title: sprintf('Cron job %s vừa phát sinh sự kiện %s', $cronJob->name, $event),
                messageLines: [
                    sprintf('URL: %s', $cronJob->url),
                    sprintf('Status: %s', $log->status?->value ?? $log->status),
                    sprintf('Status code: %s', $log->status_code ?? '--'),
                    sprintf('Duration: %s ms', $log->duration_ms ?? '--'),
                    sprintf('Message: %s', $log->error_message ?: ($log->response_body_preview ?: 'Không có')),
                ],
            ),
        };
    }

    private function telegramMessage(CronJob $cronJob, CronJobLog $log, string $event): string
    {
        return implode("\n", [
            sprintf('[AutoCron] %s', $event),
            sprintf('Job: %s', $cronJob->name),
            sprintf('Method: %s', $cronJob->method->value),
            sprintf('URL: %s', $cronJob->url),
            sprintf('Status: %s', $log->status?->value ?? $log->status),
            sprintf('Code: %s', $log->status_code ?? '--'),
            sprintf('Error: %s', $log->error_message ?: 'Không có'),
        ]);
    }

    private function sendProjectDiscordReport(CronJob $cronJob, CronJobLog $log, string $event): void
    {
        $details = [
            'Event' => $event,
            'Cron Job ID' => $cronJob->id,
            'Tên job' => $cronJob->name,
            'Method' => $cronJob->method->value,
            'URL' => $cronJob->url,
            'Status' => $log->status?->value ?? $log->status,
            'Status code' => $log->status_code ?? '--',
            'Duration (ms)' => $log->duration_ms ?? '--',
            'Error' => $log->error_message ?: ($log->response_body_preview ?: 'Không có'),
        ];

        match ($event) {
            'on_recovered' => SendMessage::sendRecoveredReport('Cron job đã hồi phục', $details),
            'on_fail', 'on_timeout', 'on_status_code_mismatch', 'on_body_mismatch' => SendMessage::sendAlertReport('Cron job phát sinh cảnh báo', $details),
            default => SendMessage::sendOpsReport('Cron job phát sinh sự kiện', $details),
        };

        if (($log->status?->value ?? $log->status) === 'blocked') {
            SendMessage::sendSecurityReport('Cron job bị chặn bởi policy', $details);
        }
    }
}
