<?php

namespace App\Features\Cron\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Arr;

class HttpTargetValidator
{
    /**
     * @return array{host:string,port:int|null,resolved_ips:array<int, string>}
     */
    public function validate(string $url, array $headers = [], ?string $body = null, array $limits = []): array
    {
        $this->ensureUrlLength($url);

        $parts = parse_url($url);
        if (! is_array($parts)) {
            throw new ApiException('URL không hợp lệ.', 422);
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new ApiException('Chỉ cho phép URL http hoặc https.', 422);
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            throw new ApiException('URL phải có hostname hợp lệ.', 422);
        }

        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            throw new ApiException('URL nội bộ localhost bị chặn bởi chính sách SSRF.', 422);
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if ($port !== null && in_array($port, [22, 25, 3306, 5432, 6379, 27017, 9200, 9300, 11211], true)) {
            throw new ApiException('Port đích không được phép.', 422);
        }

        $resolvedIps = $this->resolvePublicIps($host);
        if ($resolvedIps === []) {
            throw new ApiException('Hostname không resolve được IP public hợp lệ.', 422);
        }

        $this->ensureHeadersAreSafe($headers, (int) ($limits['max_headers_count'] ?? 10));
        $this->ensureBodySize($body, (int) ($limits['max_body_size_kb'] ?? 16));

        return [
            'host' => $host,
            'port' => $port,
            'resolved_ips' => $resolvedIps,
        ];
    }

    public function validateRedirectTarget(string $url): void
    {
        $this->validate($url);
    }

    /**
     * @return array<int, string>
     */
    private function resolvePublicIps(string $host): array
    {
        $ips = [];

        $ipv4Records = gethostbynamel($host);
        if (is_array($ipv4Records)) {
            $ips = [...$ips, ...$ipv4Records];
        }

        $aaaaRecords = dns_get_record($host, DNS_AAAA);
        if (is_array($aaaaRecords)) {
            foreach ($aaaaRecords as $record) {
                if (is_array($record) && isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        $ips = collect($ips)
            ->filter(fn (mixed $ip): bool => is_string($ip) && $ip !== '')
            ->unique()
            ->values()
            ->all();

        foreach ($ips as $ip) {
            if ($ip === '169.254.169.254') {
                throw new ApiException('Metadata IP bị chặn bởi chính sách SSRF.', 422);
            }

            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new ApiException(sprintf('Địa chỉ IP %s thuộc private/reserved range và bị chặn.', $ip), 422);
            }
        }

        return $ips;
    }

    private function ensureHeadersAreSafe(array $headers, int $maxHeadersCount): void
    {
        if (count($headers) > $maxHeadersCount) {
            throw new ApiException(sprintf('Vượt quá giới hạn %d headers.', $maxHeadersCount), 422);
        }

        $blockedHeaders = [
            'host',
            'content-length',
            'transfer-encoding',
            'connection',
            'expect',
            'x-forwarded-for',
            'x-forwarded-host',
            'x-forwarded-proto',
        ];

        foreach (Arr::wrap($headers) as $key => $value) {
            $headerName = strtolower(trim((string) $key));

            if ($headerName === '' || in_array($headerName, $blockedHeaders, true)) {
                throw new ApiException(sprintf('Header %s không được phép.', (string) $key), 422);
            }

            if (! is_scalar($value) && ! is_array($value)) {
                throw new ApiException(sprintf('Giá trị header %s không hợp lệ.', (string) $key), 422);
            }
        }
    }

    private function ensureBodySize(?string $body, int $maxBodySizeKb): void
    {
        if ($body === null) {
            return;
        }

        if (strlen($body) > ($maxBodySizeKb * 1024)) {
            throw new ApiException(sprintf('Request body vượt quá giới hạn %d KB.', $maxBodySizeKb), 422);
        }
    }

    private function ensureUrlLength(string $url): void
    {
        if (mb_strlen($url) > 2048) {
            throw new ApiException('URL vượt quá độ dài cho phép.', 422);
        }
    }
}
