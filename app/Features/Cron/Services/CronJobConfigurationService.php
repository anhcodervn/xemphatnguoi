<?php

namespace App\Features\Cron\Services;

use App\Exceptions\ApiException;
use App\Support\Enums\CronJobMethod;
use Illuminate\Support\Arr;

class CronJobConfigurationService
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $limits
     * @return array<string, mixed>
     */
    public function normalizeAndValidate(array $payload, array $limits): array
    {
        $method = strtoupper((string) ($payload['method'] ?? CronJobMethod::Get->value));
        $payload['method'] = $method;
        $payload['group_name'] = $this->normalizeNullableString($payload['group_name'] ?? null, 120);
        $payload['timezone'] = trim((string) ($payload['timezone'] ?? 'Asia/Ho_Chi_Minh')) ?: 'Asia/Ho_Chi_Minh';
        $payload['status'] = $payload['status'] ?? 'active';
        $payload['headers'] = $this->normalizeKeyValueArray($payload['headers'] ?? []);
        $payload['query_params'] = $this->normalizeKeyValueArray($payload['query_params'] ?? []);
        $payload['expected_status_codes'] = $this->normalizeStatusCodes($payload['expected_status_codes'] ?? null);
        $payload['body'] = $this->normalizeBody($payload['body'] ?? null);
        $payload['interval_seconds'] = $payload['interval_seconds'] !== null ? (int) $payload['interval_seconds'] : null;
        $payload['retry_count'] = min((int) ($payload['retry_count'] ?? 0), (int) ($limits['max_retries_per_run'] ?? 0));
        $payload['timeout_seconds'] = min((int) ($payload['timeout_seconds'] ?? 10), (int) ($limits['max_request_timeout_seconds'] ?? 10));
        $payload['connect_timeout_seconds'] = min((int) ($payload['connect_timeout_seconds'] ?? 5), $payload['timeout_seconds']);
        $payload['max_response_size_kb'] = min((int) ($payload['max_response_size_kb'] ?? 20), (int) ($limits['max_response_size_kb'] ?? 20));

        $this->assertAllowedMethod($method, $limits);
        $this->assertScheduling($payload, $limits);
        $this->assertHeadersAndBody($payload, $limits);
        $this->assertExpectedBodyRules($payload, $limits);

        return $payload;
    }

    /**
     * @param  array<int, mixed>|mixed  $items
     * @return array<string, string>
     */
    private function normalizeKeyValueArray(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])
            ->mapWithKeys(function (mixed $item, int|string $key): array {
                if (is_array($item)) {
                    $itemKey = trim((string) ($item['key'] ?? $key));
                    $itemValue = trim((string) ($item['value'] ?? ''));
                } else {
                    $itemKey = trim((string) $key);
                    $itemValue = trim((string) $item);
                }

                return $itemKey !== '' ? [$itemKey => $itemValue] : [];
            })
            ->all();
    }

    /**
     * @return array<int, int>|null
     */
    private function normalizeStatusCodes(mixed $codes): ?array
    {
        if (! is_array($codes) || $codes === []) {
            return null;
        }

        return collect($codes)
            ->map(fn (mixed $code): int => (int) $code)
            ->filter(fn (int $code): bool => $code >= 100 && $code <= 599)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeBody(mixed $body): ?string
    {
        if ($body === null) {
            return null;
        }

        if (is_string($body)) {
            return trim($body) !== '' ? trim($body) : null;
        }

        if (is_array($body)) {
            return json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $body;
    }

    private function normalizeNullableString(mixed $value, int $maxLength): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, $maxLength);
    }

    /**
     * @param  array<string, mixed>  $limits
     */
    private function assertAllowedMethod(string $method, array $limits): void
    {
        $allowedMethods = collect(Arr::wrap($limits['allowed_methods'] ?? ['GET']))
            ->map(fn (mixed $value): string => strtoupper((string) $value))
            ->filter()
            ->values()
            ->all();

        if (! in_array($method, $allowedMethods, true)) {
            throw new ApiException(sprintf('Phương thức %s không được gói hiện tại cho phép.', $method), 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $limits
     */
    private function assertScheduling(array $payload, array $limits): void
    {
        $cronExpression = trim((string) ($payload['cron_expression'] ?? ''));
        $intervalSeconds = $payload['interval_seconds'] !== null ? (int) $payload['interval_seconds'] : null;

        if ($cronExpression === '' && ($intervalSeconds === null || $intervalSeconds <= 0)) {
            throw new ApiException('Bạn phải chọn interval_seconds hoặc cron_expression.', 422);
        }

        if ($cronExpression !== '' && ! (bool) ($limits['allow_cron_expression'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ custom cron expression.', 422);
        }

        if ($intervalSeconds !== null && $intervalSeconds > 0) {
            $minimum = max(60, (int) ($limits['min_interval_seconds'] ?? 60));

            if ($intervalSeconds < $minimum) {
                throw new ApiException(sprintf('Khoảng chạy tối thiểu của gói hiện tại là %d giây.', $minimum), 422);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $limits
     */
    private function assertHeadersAndBody(array $payload, array $limits): void
    {
        $headers = Arr::wrap($payload['headers'] ?? []);
        if ($headers !== [] && ! (bool) ($limits['allow_custom_headers'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ custom headers.', 422);
        }

        $bodyType = (string) ($payload['body_type'] ?? 'none');
        $body = (string) ($payload['body'] ?? '');
        if ($bodyType !== 'none' && $body !== '' && ! (bool) ($limits['allow_custom_body'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ custom body.', 422);
        }

        $maxHeaders = max(1, (int) ($limits['max_headers_count'] ?? 5));
        if (count($headers) > $maxHeaders) {
            throw new ApiException(sprintf('Gói hiện tại chỉ cho phép tối đa %d headers.', $maxHeaders), 422);
        }

        $maxBodyBytes = max(1, (int) ($limits['max_body_size_kb'] ?? 16)) * 1024;
        if ($body !== '' && strlen($body) > $maxBodyBytes) {
            throw new ApiException(sprintf('Body vượt quá giới hạn %d KB.', (int) ($limits['max_body_size_kb'] ?? 16)), 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $limits
     */
    private function assertExpectedBodyRules(array $payload, array $limits): void
    {
        $contains = trim((string) ($payload['expected_body_contains'] ?? ''));
        $notContains = trim((string) ($payload['expected_body_not_contains'] ?? ''));

        if (($contains !== '' || $notContains !== '') && ! (bool) ($limits['allow_expected_body_check'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ kiểm tra nội dung response.', 422);
        }
    }
}
