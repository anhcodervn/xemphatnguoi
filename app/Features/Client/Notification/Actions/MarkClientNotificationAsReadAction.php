<?php

namespace App\Features\Client\Notification\Actions;

use App\Features\Client\Notification\Services\ClientNotificationService;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;

class MarkClientNotificationAsReadAction
{
    public function __construct(private readonly ClientNotificationService $clientNotificationService) {}

    public function handle(User $user, Notification $notification): NotificationRead
    {
        return $this->clientNotificationService->markAsRead($user, $notification);
    }
}
