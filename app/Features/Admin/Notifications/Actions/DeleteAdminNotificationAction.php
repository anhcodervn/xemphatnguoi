<?php

namespace App\Features\Admin\Notifications\Actions;

use App\Models\Notification;

class DeleteAdminNotificationAction
{
    public function handle(Notification $notification): void
    {
        $notification->delete();
    }
}
