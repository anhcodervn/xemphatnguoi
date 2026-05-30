<?php

namespace App\Features\Client\Contact\Services;

use App\Models\ContactFeedback;
use App\Models\Notification;
use App\Models\User;

class ContactService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function createFeedback(?User $user, array $payload): ContactFeedback
    {
        $feedback = ContactFeedback::query()->create([
            'user_id' => $user?->id,
            'name' => $payload['name'] ?? $user?->full_name ?? $user?->username ?? null,
            'email' => $payload['email'] ?? $user?->email ?? null,
            'phone' => $payload['phone'] ?? $user?->phone ?? null,
            'subject' => (string) $payload['subject'],
            'content' => (string) $payload['content'],
            'status' => ContactFeedback::STATUS_NEW,
        ]);

        Notification::query()->create([
            'user_id' => null,
            'scope' => Notification::SCOPE_SYSTEM,
            'title' => 'Góp ý mới từ người dùng',
            'content' => sprintf(
                '#%d - %s%s',
                $feedback->id,
                $feedback->subject,
                $feedback->email ? " ({$feedback->email})" : ''
            ),
            'redirect_url' => '/admin/feedbacks',
            'type' => 'feedback',
            'is_read' => false,
        ]);

        return $feedback->loadMissing(['user:id,username,full_name,email,phone']);
    }
}
