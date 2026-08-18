<?php

namespace App\Features\N8nContent\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class N8nContentApiException extends Exception
{
    public function __construct(string $message, int $status)
    {
        parent::__construct($message, $status);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->getCode());
    }
}
