<?php

namespace App\Features\Admin\Webhook\Actions;

use App\Features\Admin\Webhook\Services\AdminWebhookService;
use App\Models\Webhook;

class ShowAdminWebhookAction
{
    public function __construct(
        private readonly AdminWebhookService $adminWebhookService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(Webhook $webhook): array
    {
        return $this->adminWebhookService->webhookDetail($webhook);
    }
}
