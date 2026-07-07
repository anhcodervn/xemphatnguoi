<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiLog;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            $response = $next($request);
            $this->storeLog($request, $response, $startedAt);

            return $response;
        } catch (Throwable $throwable) {
            $this->storeThrowableLog($request, $throwable, $startedAt);

            throw $throwable;
        }
    }

    private function storeLog(Request $request, Response $response, float $startedAt): void
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('apiKey');
        if (! $apiKey instanceof ApiKey) {
            return;
        }

        ApiLog::query()->create([
            'user_id' => $apiKey->user_id,
            'api_key_id' => $apiKey->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'request_data' => $this->requestPayload($request),
            'service_response_data' => $this->serviceResponsePayload($request),
            'response_data' => $this->responsePayload($response),
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'created_at' => now(),
        ]);

        $apiKey->forceFill([
            'last_used_at' => now(),
        ])->saveQuietly();
    }

    private function storeThrowableLog(Request $request, Throwable $throwable, float $startedAt): void
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('apiKey');
        if (! $apiKey instanceof ApiKey) {
            return;
        }

        $statusCode = method_exists($throwable, 'getStatusCode')
            ? (int) $throwable->getStatusCode()
            : (int) $throwable->getCode();
        $statusCode = $statusCode >= 100 && $statusCode <= 599 ? $statusCode : 500;

        ApiLog::query()->create([
            'user_id' => $apiKey->user_id,
            'api_key_id' => $apiKey->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'request_data' => $this->requestPayload($request),
            'service_response_data' => $this->serviceResponsePayload($request),
            'response_data' => [
                'status' => false,
                'message' => Str::limit($throwable->getMessage(), 1000, '...'),
            ],
            'status_code' => $statusCode,
            'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        return [
            'query' => Arr::except($request->query(), ['api_secret', 'x-api-secret']),
            'body' => Arr::except($request->all(), ['api_secret', 'x-api-secret']),
            'route' => $request->route()?->parametersWithoutNulls() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function responsePayload(Response $response): array|string|null
    {
        if ($response instanceof JsonResponse) {
            return $response->getData(true);
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return null;
        }

        return Str::isJson($content)
            ? json_decode($content, true, flags: JSON_THROW_ON_ERROR)
            : Str::limit($content, 2000, '...');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serviceResponsePayload(Request $request): ?array
    {
        $payload = $request->attributes->get('service_response_data');

        return is_array($payload) ? $payload : null;
    }
}
