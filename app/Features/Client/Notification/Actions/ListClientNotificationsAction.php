<?php

namespace App\Features\Client\Notification\Actions;

use App\Features\Client\Notification\Services\ClientNotificationService;
use App\Models\User;

class ListClientNotificationsAction
{
    public function __construct(private readonly ClientNotificationService $clientNotificationService) {}

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function handle(User $user, array $filters = []): array
    {
        return $this->clientNotificationService->paginate($user, $filters);
    }
}
