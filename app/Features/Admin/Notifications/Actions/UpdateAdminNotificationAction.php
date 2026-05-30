<?php

namespace App\Features\Admin\Notifications\Actions;

use App\Exceptions\ApiException;
use App\Models\Notification;

class UpdateAdminNotificationAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Notification $notification, array $payload): Notification
    {
        $notification->fill([
            'scope' => $payload['scope'] ?? $notification->scope,
            'user_id' => array_key_exists('user_id', $payload) ? ($payload['user_id'] !== null ? (int) $payload['user_id'] : null) : $notification->user_id,
            'title' => $payload['title'] ?? $notification->title,
            'content' => $payload['content'] ?? $notification->content,
            'redirect_url' => $payload['redirect_url'] ?? $notification->redirect_url,
            'type' => $payload['type'] ?? $notification->type,
        ]);
        if ($notification->scope === Notification::SCOPE_SYSTEM) {
            $notification->user_id = null;
        }
        if ($notification->scope === Notification::SCOPE_USER && $notification->user_id === null) {
            throw new ApiException('Vui lòng chọn user cho thông báo người dùng.', 422);
        }
        $notification->save();

        return $notification->loadMissing(['user:id,username,full_name,email,phone']);
    }
}
