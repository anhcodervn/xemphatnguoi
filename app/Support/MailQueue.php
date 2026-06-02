<?php

namespace App\Support;

use App\Jobs\SendSystemMailJob;

class MailQueue
{
    /**
     * @param  array<int, string>  $messageLines
     */
    public function dispatch(
        string $to,
        string $subjectText,
        string $title,
        array $messageLines,
        ?string $ctaText = null,
        ?string $ctaUrl = null,
        ?string $mailer = null,
    ): void {
        SendSystemMailJob::dispatch(
            to: $to,
            subjectText: $subjectText,
            title: $title,
            messageLines: $messageLines,
            ctaText: $ctaText,
            ctaUrl: $ctaUrl,
            mailer: $mailer,
        )->onQueue('mails')->afterCommit();
    }
}
