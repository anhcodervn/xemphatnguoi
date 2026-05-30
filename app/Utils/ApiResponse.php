<?php

namespace App\Utils;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse
{
    public static function success(
        string $message = 'Success',
        array $data = [],
    ) {
        return [
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ];
    }

    public static function error(
        string $message = 'Error',
        array $data = []
    ) {
        return [
            'status'  => false,
            'message' => $message,
            'data'    => $data,
        ];
    }
}
