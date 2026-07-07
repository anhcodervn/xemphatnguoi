<?php

namespace App\Service\Captcha;

use App\Exceptions\ApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class MbbCaptcha
{
    private const DEFAULT_API_URL = 'https://captcha-mb.apibankvn.com/api/captcha/mbbank';

    private string $apiUrl;

    public function __construct(?string $apiBaseUrl = null)
    {
        $this->apiUrl = $this->resolveApiUrl($apiBaseUrl);
    }

    public function solve(string $base64): array
    {
        $normalizedBase64 = trim($base64);

        if ($normalizedBase64 === '') {
            throw new ApiException('Thiếu dữ liệu ảnh captcha MBBank.', 422);
        }

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->retry(1, 300, throw: false)
            ->post($this->apiUrl, [
                'base64' => $normalizedBase64,
            ]);

        return $this->normalizeResponse($response);
    }

    private function normalizeResponse(Response $response): array
    {
        /** @var mixed $decoded */
        $decoded = $response->json();

        if (! $response->successful()) {
            throw new ApiException('Dịch vụ giải captcha MBBank tạm thời không phản hồi.', 502, [
                'upstream_status' => $response->status(),
                'upstream_response' => is_array($decoded) ? $decoded : ['body' => $response->body()],
            ]);
        }

        if (is_array($decoded)) {
            $captcha = $this->extractCaptchaValue($decoded);

            return [
                ...$decoded,
                'solution' => $captcha !== null ? ['text' => $captcha] : null,
            ];
        }

        $body = trim($response->body());

        return [
            'success' => $body !== '',
            'captcha' => $body !== '' ? $body : null,
            'solution' => $body !== '' ? ['text' => $body] : null,
            'raw' => $body,
        ];
    }

    private function extractCaptchaValue(array $payload): ?string
    {
        $candidates = [
            Arr::get($payload, 'captcha'),
            Arr::get($payload, 'code'),
            Arr::get($payload, 'data.captcha'),
            Arr::get($payload, 'data.code'),
            Arr::get($payload, 'data'),
            Arr::get($payload, 'result'),
            Arr::get($payload, 'message'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    private function resolveApiUrl(?string $apiBaseUrl): string
    {
        $baseUrl = trim((string) self::DEFAULT_API_URL);

        return $baseUrl !== '' ? $baseUrl : self::DEFAULT_API_URL;
    }
}
