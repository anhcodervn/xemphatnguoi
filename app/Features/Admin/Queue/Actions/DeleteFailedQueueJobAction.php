<?php

namespace App\Features\Admin\Queue\Actions;

use App\Features\Admin\Queue\Services\AdminQueueService;

class DeleteFailedQueueJobAction
{
    public function __construct(
        private readonly AdminQueueService $adminQueueService,
    ) {
    }

    public function handle(int|string $id): void
    {
        $this->adminQueueService->deleteFailedJob($id);
    }
}
