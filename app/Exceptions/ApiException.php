<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        int $status = 400,
        protected array $data = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function render(Request $request): ?JsonResponse
    {
        if ($request->expectsJson() || $request->is('api/*') || $request->routeIs('api.*')) {
            $payload = array_merge([
                'status' => false,
                'message' => $this->getMessage(),
            ], $this->data);

            return response()->json($payload, $this->getCode() ?: 400);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
