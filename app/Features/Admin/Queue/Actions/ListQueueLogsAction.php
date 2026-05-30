<?php

namespace App\Features\Admin\Queue\Actions;

use App\Features\Admin\Queue\Services\AdminQueueService;

class ListQueueLogsAction
{
    public function __construct(
        private readonly AdminQueueService $adminQueueService,
    ) {
    }

    /**
     * @param array{queue?:string,status?:string,search?:string,per_page?:int} $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters): array
    {
        return $this->adminQueueService->logs($filters);
    }
}
