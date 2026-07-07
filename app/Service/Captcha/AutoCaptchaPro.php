<?php

namespace App\Service\Captcha;

use App\Exceptions\ApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class AutoCaptchaPro
{
    private const DEFAULT_PROCESS_URL = 'https://autocaptcha.pro/apiv3/process';

    private const DEFAULT_BALANCE_URL = 'https://autocaptcha.pro/apiv3/balance';

    private string $processUrl;

    private string $balanceUrl;

    private string $apiKey;

    public function __construct(array $credentials = [], ?string $apiBaseUrl = null)
    {
        $this->processUrl = $this->resolveProcessUrl($apiBaseUrl);
        $this->balanceUrl = $this->resolveBalanceUrl($apiBaseUrl);
        $this->apiKey = $this->resolveApiKey($credentials);
    }

    public function balance(): array
    {
        $this->ensureConfigured();

        $response = $this->newRequest()->get($this->balanceUrl, [
            'key' => $this->apiKey,
        ]);

        return $this->normalizeResponse($response);
    }

    public function imageToText(string $image, bool $caseSensitive = false, string $module = 'common'): array
    {
        // dd($image, $caseSensitive, $module);
        $normalizedImage = trim($image);

        if ($normalizedImage === '') {
            throw new ApiException('Thiếu dữ liệu ảnh captcha.', 422);
        }

        return $this->process([
            'type' => 'imagetotext',
            'img' => $normalizedImage,
            'module' => trim($module) !== '' ? trim($module) : 'common',
            'casesensitive' => $caseSensitive,
        ]);
    }

    public function recaptchaV2(string $siteKey, string $pageUrl, bool $invisible = false): array
    {
        return $this->process(array_filter([
            'type' => 'recaptchav2',
            'googlesitekey' => $this->requireValue($siteKey, 'Thiếu website key cho ReCaptcha v2.'),
            'pageurl' => $this->requireValue($pageUrl, 'Thiếu website url cho ReCaptcha v2.'),
            'invi' => $invisible ? '1' : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function recaptchaV3(
        string $siteKey,
        string $pageUrl,
        ?string $action = null,
        ?string $proxy = null,
        ?float $score = null,
    ): array {
        $resolvedScore = $this->normalizeScore($score);

        return $this->process(array_filter([
            'type' => $resolvedScore >= 0.7 ? 'recaptchav3highscore' : 'recaptchav3',
            'googlesitekey' => $this->requireValue($siteKey, 'Thiếu website key cho ReCaptcha v3.'),
            'pageurl' => $this->requireValue($pageUrl, 'Thiếu website url cho ReCaptcha v3.'),
            'score' => $resolvedScore < 0.7 ? $resolvedScore : null,
            'proxy' => $this->normalizeOptionalString($proxy),
            'actionv3' => $this->normalizeOptionalString($action),
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function cloudflare(string $siteKey, string $pageUrl): array
    {
        return $this->process([
            'type' => 'cloudflare',
            'websitekey' => $this->requireValue($siteKey, 'Thiếu website key cho Cloudflare Turnstile.'),
            'pageurl' => $this->requireValue($pageUrl, 'Thiếu website url cho Cloudflare Turnstile.'),
        ]);
    }

    public function geetestV4(string $captchaId, string $pageUrl): array
    {
        return $this->process([
            'type' => 'geetestv4',
            'captchaId' => $this->requireValue($captchaId, 'Thiếu captcha id cho GeeTest v4.'),
            'pageurl' => $this->requireValue($pageUrl, 'Thiếu website url cho GeeTest v4.'),
        ]);
    }

    public function process(array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->newRequest()
            ->asJson()
            ->post($this->processUrl, [
                'key' => $this->apiKey,
                ...$payload,
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

        if (($data['success'] ?? false) !== true) {
            throw new ApiException((string) ($data['message'] ?? 'Hệ thống xử lý captcha thất bại.'), 422, [
                'upstream_status' => $response->status(),
                'upstream_response' => $data,
            ]);
        }

        $solution = $this->normalizeSolution(Arr::get($data, 'captcha'));

        if ($solution !== null) {
            $data['solution'] = $solution;
        }

        return $data;
    }

    private function normalizeSolution(mixed $captcha): ?array
    {
        if (is_string($captcha)) {
            $normalizedText = trim($captcha);

            return $normalizedText !== ''
                ? ['text' => $normalizedText]
                : null;
        }

        if (! is_array($captcha) || $captcha === []) {
            return null;
        }

        $solution = array_filter([
            'token' => $this->normalizeOptionalString(Arr::get($captcha, 'token')),
            'clearance' => $this->normalizeOptionalString(Arr::get($captcha, 'clearance')),
            'captcha_id' => $this->normalizeOptionalString(Arr::get($captcha, 'captcha_id')),
            'captcha_output' => $this->normalizeOptionalString(Arr::get($captcha, 'captcha_output')),
            'gen_time' => $this->normalizeOptionalString(Arr::get($captcha, 'gen_time')),
            'lot_number' => $this->normalizeOptionalString(Arr::get($captcha, 'lot_number')),
            'pass_token' => $this->normalizeOptionalString(Arr::get($captcha, 'pass_token')),
            'risk_type' => $this->normalizeOptionalString(Arr::get($captcha, 'risk_type')),
            'challenge' => $this->normalizeOptionalString(Arr::get($captcha, 'challenge')),
            'validate' => $this->normalizeOptionalString(Arr::get($captcha, 'validate')),
            'seccode' => $this->normalizeOptionalString(Arr::get($captcha, 'seccode')),
        ], static fn (mixed $value): bool => $value !== null);

        $solution['raw'] = $captcha;

        return $solution;
    }

    private function ensureConfigured(): void
    {
        if ($this->apiKey !== '') {
            return;
        }

        throw new ApiException('Cấu hình dịch vụ captcha chưa hoàn tất.', 422);
    }

    private function resolveProcessUrl(?string $apiBaseUrl): string
    {
        $baseUrl = trim((string) $apiBaseUrl);

        if ($baseUrl === '') {
            return self::DEFAULT_PROCESS_URL;
        }

        if (str_ends_with($baseUrl, '/balance')) {
            return substr($baseUrl, 0, -strlen('/balance')).'/process';
        }

        if (str_ends_with($baseUrl, '/process')) {
            return $baseUrl;
        }

        if (str_ends_with($baseUrl, '/apiv3')) {
            return $baseUrl.'/process';
        }

        return rtrim($baseUrl, '/').'/apiv3/process';
    }

    private function resolveBalanceUrl(?string $apiBaseUrl): string
    {
        $baseUrl = trim((string) $apiBaseUrl);

        if ($baseUrl === '') {
            return self::DEFAULT_BALANCE_URL;
        }

        if (str_ends_with($baseUrl, '/process')) {
            return substr($baseUrl, 0, -strlen('/process')).'/balance';
        }

        if (str_ends_with($baseUrl, '/balance')) {
            return $baseUrl;
        }

        if (str_ends_with($baseUrl, '/apiv3')) {
            return $baseUrl.'/balance';
        }

        return rtrim($baseUrl, '/').'/apiv3/balance';
    }

    private function resolveApiKey(array $credentials): string
    {
        $apiKey = Arr::first([
            Arr::get($credentials, 'api_key'),
            Arr::get($credentials, 'key'),
            Arr::get($credentials, 'token'),
            Arr::get($credentials, 'access_key'),
        ], static fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        return is_string($apiKey) ? trim($apiKey) : '';
    }

    private function normalizeOptionalString(mixed $value): ?string
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

    private function normalizeScore(?float $score): float
    {
        $resolvedScore = $score ?? 0.6;

        if ($resolvedScore < 0.1) {
            return 0.1;
        }

        if ($resolvedScore > 0.9) {
            return 0.9;
        }

        return round($resolvedScore, 1);
    }
}
