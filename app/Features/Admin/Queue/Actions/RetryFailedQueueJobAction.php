<?php

namespace App\Features\Admin\Queue\Actions;

use App\Features\Admin\Queue\Services\AdminQueueService;

class RetryFailedQueueJobAction
{
    public function __construct(
        private readonly AdminQueueService $adminQueueService,
    ) {}

    public function handle(string $uuid): void
    {
        $this->adminQueueService->replayFailedJob($uuid);
    }
}
