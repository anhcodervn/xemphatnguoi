<?php

namespace App\Utils;

class SendMessage{

    private static function tele($message, $chatId, $token){
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
        ];
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
            ],
        ];
        $context = stream_context_create($options);
        file_get_contents($url, false, $context);

    }

    public static function sendTelegram($message, $chatId = "-5237556794"){
        $token = "5549496111:AAHYXIIi5XGd8JkCbx3Lk0DMHrLA45a6ODk";
        self::tele($message, $chatId, $token);
    }
}