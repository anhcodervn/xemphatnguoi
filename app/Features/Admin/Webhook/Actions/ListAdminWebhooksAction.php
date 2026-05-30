<?php

namespace App\Features\Admin\Webhook\Actions;

use App\Features\Admin\Webhook\Services\AdminWebhookService;

class ListAdminWebhooksAction
{
    public function __construct(
        private readonly AdminWebhookService $adminWebhookService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        return $this->adminWebhookService->paginateWebhooks($filters);
    }
}
