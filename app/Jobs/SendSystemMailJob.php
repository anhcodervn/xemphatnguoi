<?php

namespace App\Jobs;

use App\Mail\SystemNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSystemMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param array<int, string> $messageLines
     */
    public function __construct(
        public string $to,
        public string $subjectText,
        public string $title,
        public array $messageLines,
        public ?string $ctaText = null,
        public ?string $ctaUrl = null,
        public ?string $mailer = null,
    ) {
    }

    public function handle(): void
    {
        $mailer = $this->mailer ? Mail::mailer($this->mailer) : Mail::mailer(config('mail.default'));

        $mailer->to($this->to)->send(new SystemNotificationMail(
            subjectText: $this->subjectText,
            title: $this->title,
            messageLines: $this->messageLines,
            ctaText: $this->ctaText,
            ctaUrl: $this->ctaUrl,
        ));
    }
}
