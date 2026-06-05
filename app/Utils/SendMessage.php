<?php

namespace App\Utils;

use DateTimeInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SendMessage
{
    private static function tele($message, $chatId, $token)
    {
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
        ];
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
            ],
        ];
        $context = stream_context_create($options);
        file_get_contents($url, false, $context);

    }

    public static function sendTelegram($message, $chatId = '-5237556794')
    {
        $token = '5549496111:AAHYXIIi5XGd8JkCbx3Lk0DMHrLA45a6ODk';
        self::tele($message, $chatId, $token);
    }

    public static function sendDiscord($message, $type): void
    {
        if (app()->environment('testing')) {
            return;
        }

        switch ($type) {
            case 'queue':
                $webhookUrl = 'https://discord.com/api/webhooks/1512374229176291339/Qg7-YgIUP47ZJhirNg8uD425RVuWQFffxKGEtDTluTG_zKKP2ckUG8JFurdql-KcCVkI';
                break;
            case 'info':
                $webhookUrl = 'https://discord.com/api/webhooks/1512357656759762947/4tNR4x_6Zyvz5zCaBsxMNsqEvWlxfhHqbzNXJG-yp_4k3ogJ30H2ha8qmen51qtahUUC';
                break;
            default:
                return;
        }

        $url = $webhookUrl ?: config('services.discord.webhook_url');

        if (! is_string($url) || trim($url) === '') {
            throw new \InvalidArgumentException('Discord webhook URL is not configured.');
        }

        Http::connectTimeout(5)
            ->timeout(10)
            ->post($url, [
                'content' => $message,
            ])
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function sendInfoReport(string $title, array $details = []): void
    {
        self::safeSendDiscord(
            self::formatDiscordReport('INFO', $title, $details),
            'info',
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function sendQueueReport(string $title, array $details = []): void
    {
        self::safeSendDiscord(
            self::formatDiscordReport('QUEUE', $title, $details),
            'queue',
        );
    }

    private static function safeSendDiscord(string $message, string $type): void
    {
        try {
            self::sendDiscord($message, $type);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private static function formatDiscordReport(string $channel, string $title, array $details): string
    {
        $lines = [
            sprintf('**[%s] %s**', $channel, $title),
            sprintf('- Thời gian: `%s`', now()->format('Y-m-d H:i:s T')),
        ];

        foreach ($details as $label => $value) {
            $lines[] = sprintf('- %s: %s', $label, self::normalizeDiscordValue($value));
        }

        return Str::limit(implode(PHP_EOL, $lines), 1900);
    }

    private static function normalizeDiscordValue(mixed $value): string
    {
        if ($value === null) {
            return '`null`';
        }

        if ($value instanceof DateTimeInterface) {
            return sprintf('`%s`', $value->format('Y-m-d H:i:s T'));
        }

        if (is_bool($value)) {
            return $value ? '`true`' : '`false`';
        }

        if (is_scalar($value)) {
            return sprintf('`%s`', Str::limit((string) $value, 300));
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return sprintf('`%s`', Str::limit($encoded ?: '[unserializable]', 300));
    }
}
