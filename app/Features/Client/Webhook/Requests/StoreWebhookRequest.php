<?php

namespace App\Features\Client\Webhook\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->string('name')->trim()->value(),
            'url' => $this->string('url')->trim()->value(),
            'event_keyword' => $this->string('event_keyword')->trim()->lower()->value(),
            'status' => $this->string('status')->trim()->lower()->value(),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'event_keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $url = (string) $this->input('url', '');

            if ($url !== '' && ! $this->isSafeWebhookUrl($url)) {
                $validator->errors()->add('url', 'URL webhook không được trỏ tới localhost, IP nội bộ hoặc mạng private.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => 'ten webhook',
            'url' => 'URL webhook',
            'event_keyword' => 'tu khoa event',
            'status' => 'trang thai',
        ];
    }

    private function isSafeWebhookUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicIp($host);
        }

        $resolved = false;

        foreach (gethostbynamel($host) ?: [] as $ip) {
            $resolved = true;

            if (! $this->isPublicIp($ip)) {
                return false;
            }
        }

        foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            $ip = $record['ipv6'] ?? null;

            if (is_string($ip)) {
                $resolved = true;

                if (! $this->isPublicIp($ip)) {
                    return false;
                }
            }
        }

        return $resolved;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
