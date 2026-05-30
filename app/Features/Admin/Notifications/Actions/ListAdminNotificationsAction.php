<?php

namespace App\Features\Admin\Notifications\Actions;

use App\Features\Admin\Notifications\Services\AdminNotificationService;

class ListAdminNotificationsAction
{
    public function __construct(private readonly AdminNotificationService $notificationService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        return $this->notificationService->paginate($filters);
    }
}
