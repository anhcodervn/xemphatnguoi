<?php

namespace App\Features\Admin\Queue\Actions;

use App\Features\Admin\Queue\Services\AdminQueueService;
use App\Models\QueueLog;

class ReplayQueueLogAction
{
    public function __construct(
        private readonly AdminQueueService $adminQueueService,
    ) {}

    public function handle(QueueLog $queueLog): void
    {
        $this->adminQueueService->replayQueueLog($queueLog);
    }
}
