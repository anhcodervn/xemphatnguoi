<?php

namespace App\Service\Captcha;

use App\Exceptions\ApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class Captcha69
{
    private const DEFAULT_BASE_URL = 'https://captcha69.com';

    private string $createTaskUrl;

    private string $getTaskResultUrl;

    private string $balanceUrl;

    private string $clientKey;

    public function __construct(array $credentials = [], ?string $apiBaseUrl = null)
    {
        $this->createTaskUrl = $this->resolveCreateTaskUrl($apiBaseUrl);
        $this->getTaskResultUrl = $this->resolveGetTaskResultUrl($apiBaseUrl);
        $this->balanceUrl = $this->resolveBalanceUrl($apiBaseUrl);
        $this->clientKey = $this->resolveClientKey($credentials);
    }

    public function balance(): array
    {
        $this->ensureConfigured();

        $response = $this->newRequest()
            ->asJson()
            ->post($this->balanceUrl, [
                'clientKey' => $this->clientKey,
            ]);

        return $this->normalizeResponse($response);
    }

    public function createTurnstileTask(
        string $websiteUrl,
        string $websiteKey,
        ?string $userAgent = null,
        ?string $pageAction = null,
        ?string $data = null,
        ?array $proxy = null,
    ): array {
        $task = array_filter([
            'type' => 'TurnstileTask',
            'websiteURL' => $this->requireValue($websiteUrl, 'Thiếu website url cho Cloudflare Turnstile.'),
            'websiteKey' => $this->requireValue($websiteKey, 'Thiếu website key cho Cloudflare Turnstile.'),
            'userAgent' => $this->normalizeOptionalString($userAgent),
            'pageAction' => $this->normalizeOptionalString($pageAction),
            'data' => $this->normalizeOptionalString($data),
            ...$this->normalizeProxy($proxy),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $this->createTask($task);
    }

    public function createTask(array $task): array
    {
        $this->ensureConfigured();

        $response = $this->newRequest()
            ->asJson()
            ->post($this->createTaskUrl, [
                'clientKey' => $this->clientKey,
                'task' => $task,
            ]);

        return $this->normalizeResponse($response);
    }

    public function getTaskResult(string|int $taskId): array
    {
        $this->ensureConfigured();

        $response = $this->newRequest()
            ->asJson()
            ->post($this->getTaskResultUrl, [
                'clientKey' => $this->clientKey,
                'taskId' => is_numeric((string) $taskId) ? (int) $taskId : (string) $taskId,
            ]);

        return $this->normalizeResponse($response);
    }

    private function newRequest()
    {
        return Http::timeout(30)
            ->connectTimeout(10)
            ->acceptJson()
            ->retry(2, 500, throw: false);
    }

    private function normalizeResponse(Response $response): array
    {
        /** @var mixed $decoded */
        $decoded = $response->json();
        /** @var array<string, mixed> $data */
        $data = is_array($decoded) ? $decoded : [];

        if ($response->failed()) {
            throw new ApiException('Cụm xử lý captcha tạm thời không phản hồi.', 502, [
                'upstream_status' => $response->status(),
                'upstream_response' => $data !== [] ? $data : ['body' => $response->body()],
            ]);
        }

        if ($data === []) {
            throw new ApiException('Phản hồi từ nguồn captcha không hợp lệ.', 502, [
                'upstream_status' => $response->status(),
                'upstream_response' => ['body' => $response->body()],
            ]);
        }

        if ((int) Arr::get($data, 'errorId', 0) !== 0) {
            throw new ApiException(
                (string) (
                    Arr::get($data, 'errorDescription')
                    ?: Arr::get($data, 'errorCode')
                    ?: 'Hệ thống xử lý captcha thất bại.'
                ),
                422,
                [
                    'upstream_status' => $response->status(),
                    'upstream_response' => $data,
                ],
            );
        }

        $solution = $this->normalizeSolution(Arr::get($data, 'solution'));

        if ($solution !== null) {
            $data['solution'] = $solution;
        }

        return $data;
    }

    private function normalizeSolution(mixed $solution): ?array
    {
        if (! is_array($solution) || $solution === []) {
            return null;
        }

        $token = $this->normalizeOptionalString(Arr::get($solution, 'token'));

        if ($token === null) {
            return null;
        }

        return array_filter([
            'token' => $token,
            'user_agent' => $this->normalizeOptionalString(Arr::get($solution, 'userAgent')),
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>|null  $proxy
     * @return array<string, string|int|null>
     */
    private function normalizeProxy(?array $proxy): array
    {
        if (! is_array($proxy) || $proxy === []) {
            return [];
        }

        $proxyPort = Arr::get($proxy, 'port');
        $normalizedPort = is_numeric($proxyPort) ? (int) $proxyPort : null;

        return [
            'proxyType' => $this->normalizeOptionalString((string) Arr::get($proxy, 'type', '')),
            'proxyAddress' => $this->normalizeOptionalString((string) Arr::get($proxy, 'address', '')),
            'proxyPort' => $normalizedPort,
            'proxyLogin' => $this->normalizeOptionalString((string) Arr::get($proxy, 'login', '')),
            'proxyPassword' => $this->normalizeOptionalString((string) Arr::get($proxy, 'password', '')),
        ];
    }

    private function ensureConfigured(): void
    {
        if ($this->clientKey !== '') {
            return;
        }

        throw new ApiException('Cấu hình dịch vụ captcha chưa hoàn tất.', 422);
    }

    private function resolveCreateTaskUrl(?string $apiBaseUrl): string
    {
        $baseUrl = trim((string) $apiBaseUrl);

        if ($baseUrl === '') {
            return self::DEFAULT_BASE_URL.'/createTask';
        }

        if (str_ends_with($baseUrl, '/createTask')) {
            return $baseUrl;
        }

        if (str_ends_with($baseUrl, '/getTaskResult')) {
            return substr($baseUrl, 0, -strlen('/getTaskResult')).'/createTask';
        }

        return rtrim($baseUrl, '/').'/createTask';
    }

    private function resolveGetTaskResultUrl(?string $apiBaseUrl): string
    {
        $baseUrl = trim((string) $apiBaseUrl);

        if ($baseUrl === '') {
            return self::DEFAULT_BASE_URL.'/getTaskResult';
        }

        if (str_ends_with($baseUrl, '/getTaskResult')) {
            return $baseUrl;
        }

        if (str_ends_with($baseUrl, '/createTask')) {
            return substr($baseUrl, 0, -strlen('/createTask')).'/getTaskResult';
        }

        return rtrim($baseUrl, '/').'/getTaskResult';
    }

    private function resolveBalanceUrl(?string $apiBaseUrl): string
    {
        $baseUrl = trim((string) $apiBaseUrl);

        if ($baseUrl === '') {
            return self::DEFAULT_BASE_URL.'/getBalance';
        }

        if (str_ends_with($baseUrl, '/getBalance')) {
            return $baseUrl;
        }

        if (str_ends_with($baseUrl, '/createTask')) {
            return substr($baseUrl, 0, -strlen('/createTask')).'/getBalance';
        }

        if (str_ends_with($baseUrl, '/getTaskResult')) {
            return substr($baseUrl, 0, -strlen('/getTaskResult')).'/getBalance';
        }

        return rtrim($baseUrl, '/').'/getBalance';
    }

    private function resolveClientKey(array $credentials): string
    {
        $clientKey = Arr::first([
            Arr::get($credentials, 'client_key'),
            Arr::get($credentials, 'api_key'),
            Arr::get($credentials, 'key'),
            Arr::get($credentials, 'token'),
        ], static fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        return is_string($clientKey) ? trim($clientKey) : '';
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function requireValue(string $value, string $message): string
    {
        $normalized = trim($value);

        if ($normalized !== '') {
            return $normalized;
        }

        throw new ApiException($message, 422);
    }
}
