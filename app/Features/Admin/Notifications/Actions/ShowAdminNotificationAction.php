<?php

namespace App\Features\Admin\Notifications\Actions;

use App\Models\Notification;

class ShowAdminNotificationAction
{
    public function handle(Notification $notification): Notification
    {
        return $notification->loadMissing(['user:id,username,full_name,email,phone']);
    }
}
