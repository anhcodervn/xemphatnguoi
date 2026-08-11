<?php

namespace App\Jobs;

use App\Features\Support\Services\DiscordSupportNotifierService;
use App\Models\SupportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendSupportMessageToDiscord implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 8;

    public int $uniqueFor = 600;

    public function __construct(public readonly int $messageId)
    {
        $this->onQueue('default');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function uniqueId(): string
    {
        return (string) $this->messageId;
    }

    public function handle(DiscordSupportNotifierService $notifier): void
    {
        $message = SupportMessage::query()
            ->where('sender_role', SupportMessage::ROLE_USER)
            ->find($this->messageId);

        if ($message instanceof SupportMessage) {
            $notifier->send($message);
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception instanceof Throwable) {
            report($exception);
        }
    }
}
