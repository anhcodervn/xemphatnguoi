<?php

namespace App\Http\Middleware;

use App\Features\TrafficFine\Services\ApiLookupBillingService;
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
        } catch (Throwable $throwable) {
            try {
                $this->storeThrowableLog($request, $throwable, $startedAt);
            } catch (Throwable $loggingException) {
                report($loggingException);
            }

            throw $throwable;
        }

        try {
            $this->storeLog($request, $response, $startedAt);
        } catch (Throwable $loggingException) {
            report($loggingException);
        }

        return $response;
    }

    private function storeLog(Request $request, Response $response, float $startedAt): void
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('apiKey');
        if (! $apiKey instanceof ApiKey) {
            return;
        }

        $this->persistLog($request, [
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
            'wallet_transaction_id' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_TRANSACTION_ID),
            'unit_price' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_UNIT_PRICE, 0),
            'charged_amount' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_CHARGED_AMOUNT, 0),
            'billing_status' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_STATUS, 'not_billable'),
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

        $this->persistLog($request, [
            'user_id' => $apiKey->user_id,
            'api_key_id' => $apiKey->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'request_data' => $this->requestPayload($request),
            'service_response_data' => $this->serviceResponsePayload($request),
            'response_data' => [
                'status' => false,
                'message' => 'Request could not be completed.',
            ],
            'status_code' => $statusCode,
            'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'wallet_transaction_id' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_TRANSACTION_ID),
            'unit_price' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_UNIT_PRICE, 0),
            'charged_amount' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_CHARGED_AMOUNT, 0),
            'billing_status' => $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_STATUS, 'not_billable'),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function persistLog(Request $request, array $attributes): void
    {
        $apiLogId = $request->attributes->get(ApiLookupBillingService::ATTRIBUTE_LOG_ID);

        if (is_int($apiLogId)) {
            ApiLog::query()->whereKey($apiLogId)->update($attributes);

            return;
        }

        ApiLog::query()->create($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        return $this->sanitizeSensitiveData([
            'query' => Arr::except($request->query(), ['api_secret', 'x-api-secret']),
            'body' => Arr::except($request->all(), ['api_secret', 'x-api-secret']),
            'route' => $request->route()?->parametersWithoutNulls() ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function responsePayload(Response $response): array|string|null
    {
        if ($response instanceof JsonResponse) {
            return $this->sanitizeSensitiveData($response->getData(true));
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return null;
        }

        return Str::isJson($content)
            ? $this->sanitizeSensitiveData(json_decode($content, true, flags: JSON_THROW_ON_ERROR))
            : Str::limit($content, 2000, '...');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serviceResponsePayload(Request $request): ?array
    {
        $payload = $request->attributes->get('service_response_data');

        return is_array($payload) ? $this->sanitizeSensitiveData($payload) : null;
    }

    /**
     * Không lưu credential vào lịch sử API dạng văn bản thuần.
     *
     * @param  array<string, mixed>|list<mixed>  $data
     * @return array<string, mixed>|list<mixed>
     */
    private function sanitizeSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'api_secret',
            'x-api-secret',
            'api_key',
            'x-api-key',
            'api_token',
            'authorization',
            'token',
            'url',
            'debug_url',
            'debug_token',
            'provider',
            'provider_url',
            'password',
            'username',
            'host',
            'access_key',
            'provider_code',
            'external_order_id',
            'connection',
        ];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = filled($value) ? '[REDACTED]' : null;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeSensitiveData($value);
            }
        }

        return $data;
    }
}
