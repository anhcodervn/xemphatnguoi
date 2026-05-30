<?php

namespace App\Features\Admin\Queue\Actions;

use App\Features\Admin\Queue\Services\AdminQueueService;

class GetQueueOverviewAction
{
    public function __construct(
        private readonly AdminQueueService $adminQueueService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return $this->adminQueueService->overview();
    }
}
