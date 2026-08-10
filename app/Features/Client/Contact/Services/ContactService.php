<?php

namespace App\Features\Client\Contact\Services;

use App\Models\ContactFeedback;
use App\Models\User;
use App\Utils\SendMessage;

class ContactService
{
    /**
     * @param  array<string, mixed>  $payload
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

        $feedback->loadMissing(['user:id,username,full_name,email,phone']);

        SendMessage::sendFeedbackReport('Có góp ý mới cần xử lý', [
            'Mã góp ý' => $feedback->id,
            'User ID' => $feedback->user_id,
            'Người gửi' => $feedback->name ?: $feedback->user?->name ?: '--',
            'Email' => $feedback->email ?: '--',
            'Số điện thoại' => $feedback->phone ?: '--',
            'Tiêu đề' => $feedback->subject,
            'Nội dung' => $feedback->content,
            'Admin kiểm tra' => url('/admin/feedbacks'),
        ]);

        return $feedback;
    }
}
