<?php

namespace App\Features\Captcha\Services;

use App\Models\CaptchaSource;
use App\Service\Captcha\AutoCaptchaPro;
use App\Service\Captcha\Captcha69;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class CaptchaSourceBalanceService
{
    /**
     * @return array{total:int, updated:int, skipped:int, failed:int}
     */
    public function syncActiveSources(): array
    {
        $sources = CaptchaSource::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        /** @var CaptchaSource $source */
        foreach ($sources as $source) {
            if (! $this->supportsBalance($source)) {
                $skipped++;

                continue;
            }

            try {
                $balance = $this->fetchBalance($source);

                $source->forceFill([
                    'balance' => $balance,
                ])->save();

                $updated++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return [
            'total' => $sources->count(),
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    public function supportsBalance(CaptchaSource $source): bool
    {
        return $this->resolveDriver($source) !== null;
    }

    private function fetchBalance(CaptchaSource $source): string
    {
        [$driver, $payload] = match ($this->resolveDriver($source)) {
            'autocaptchapro' => ['autocaptchapro', $this->autoCaptchaPro($source)->balance()],
            'captcha69' => ['captcha69', $this->captcha69($source)->balance()],
            default => throw new \RuntimeException('Nguồn captcha chưa hỗ trợ đồng bộ số dư.'),
        };

        $balance = $this->extractBalance($payload);

        if ($balance !== null) {
            return $balance;
        }

        throw new \RuntimeException(sprintf(
            'Không đọc được số dư từ nguồn captcha [%s].',
            $driver,
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractBalance(array $payload): ?string
    {
        $candidates = Collection::make([
            Arr::get($payload, 'balance'),
            Arr::get($payload, 'data.balance'),
            Arr::get($payload, 'result.balance'),
            Arr::get($payload, 'wallet.balance'),
            Arr::get($payload, 'credits'),
        ]);

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeBalanceValue($candidate);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeBalanceValue(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 4, '.', '');
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(',', '.', $value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 4, '.', '');
    }

    private function resolveDriver(CaptchaSource $source): ?string
    {
        return match (Str::of((string) $source->driver)->trim()->lower()->value()) {
            'autocaptchapro' => 'autocaptchapro',
            'captcha69' => 'captcha69',
            default => null,
        };
    }

    private function autoCaptchaPro(CaptchaSource $source): AutoCaptchaPro
    {
        return new AutoCaptchaPro($source->credentials ?? [], $source->api_base_url);
    }

    private function captcha69(CaptchaSource $source): Captcha69
    {
        return new Captcha69($source->credentials ?? [], $source->api_base_url);
    }
}
