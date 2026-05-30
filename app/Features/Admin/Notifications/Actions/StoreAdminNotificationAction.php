<?php

namespace App\Features\Admin\Notifications\Actions;

use App\Exceptions\ApiException;
use App\Models\Notification;

class StoreAdminNotificationAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): Notification
    {
        $scope = (string) ($payload['scope'] ?? Notification::SCOPE_USER);
        $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;

        if ($scope === Notification::SCOPE_USER && $userId === null) {
            throw new ApiException('Vui lòng chọn user cho thông báo người dùng.', 422);
        }

        $notification = Notification::query()->create([
            'scope' => $scope,
            'user_id' => $scope === Notification::SCOPE_SYSTEM ? null : $userId,
            'title' => (string) $payload['title'],
            'content' => (string) $payload['content'],
            'redirect_url' => $payload['redirect_url'] ?? null,
            'type' => $payload['type'] ?? null,
            'is_read' => false,
        ]);

        return $notification->loadMissing(['user:id,username,full_name,email,phone']);
    }
}
