<?php

namespace App\Features\Admin\Mail\Actions;

use App\Features\Admin\Mail\Services\AdminMailService;

class SendUserMailAction
{
    public function __construct(
        private readonly AdminMailService $adminMailService,
    ) {
    }

    /**
     * @param array{
     *   recipient_type:string,
     *   user_ids?:array<int,int>,
     *   subject:string,
     *   title:string,
     *   message:string,
     *   cta_text?:?string,
     *   cta_url?:?string
     * } $payload
     * @return array{queued:int,skipped:int}
     */
    public function handle(array $payload): array
    {
        return $this->adminMailService->sendToUsers($payload);
    }
}
