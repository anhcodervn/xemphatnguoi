<?php

namespace App\Features\Admin\Webhook\Actions;

use App\Features\Admin\Webhook\Services\AdminWebhookService;
use App\Models\Webhook;

class ListWebhookLogsAction
{
    public function __construct(
        private readonly AdminWebhookService $adminWebhookService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(Webhook $webhook, array $filters = []): array
    {
        return $this->adminWebhookService->paginateLogs($webhook, $filters);
    }
}
