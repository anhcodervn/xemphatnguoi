<?php

namespace App\Features\Support\Services;

use App\Models\SupportMessage;
use App\Utils\SendMessage;
use Illuminate\Support\Str;

class DiscordSupportNotifierService
{
    public function send(SupportMessage $message): void
    {
        $message->loadMissing('conversation.user');
        $conversation = $message->conversation;
        $user = $conversation->user;
        $preview = Str::of($message->message)
            ->replace(['@everyone', '@here'], ['＠everyone', '＠here'])
            ->limit(500)
            ->toString();

        SendMessage::sendSupportReport('Tin nhắn hỗ trợ mới', [
            'Conversation ID' => $conversation->id,
            'Message ID' => $message->id,
            'User ID' => $user->id,
            'Username' => $user->username,
            'Nội dung' => $preview,
            'Admin kiểm tra' => rtrim((string) config('app.url'), '/')."/admin/support?conversation={$conversation->id}",
        ]);
    }
}
