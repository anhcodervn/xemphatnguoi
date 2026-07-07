<?php

namespace App\Features\Captcha\Services;

use App\Models\CaptchaSource;
use App\Service\Captcha\AutoCaptchaPro;
use App\Service\Captcha\Captcha69;
use App\Utils\SendMessage;
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
                $settings = is_array($source->settings) ? $source->settings : [];
                $settings = $this->updateBalanceAlertState(
                    source: $source,
                    settings: $settings,
                    balance: $balance,
                );

                $source->forceFill([
                    'balance' => $balance,
                    'settings' => $settings,
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

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function updateBalanceAlertState(CaptchaSource $source, array $settings, string $balance): array
    {
        $threshold = max(0, (float) config('services.captcha.source_low_balance_threshold', 50000));
        $channel = trim((string) config('services.captcha.source_low_balance_channel', 'alerts'));
        $balanceValue = (float) $balance;
        $monitor = is_array(Arr::get($settings, 'balance_monitor')) ? Arr::get($settings, 'balance_monitor') : [];
        $wasAlerted = (bool) Arr::get($monitor, 'low_balance_alert_sent', false);

        if ($balanceValue <= $threshold) {
            if (! $wasAlerted && $channel !== '') {
                $this->sendLowBalanceAlert($source, $balance, $threshold, $channel);
            }

            $monitor['low_balance_alert_sent'] = true;
            $monitor['low_balance_alert_sent_at'] = now()->toISOString();
            $settings['balance_monitor'] = $monitor;

            return $settings;
        }

        if ($wasAlerted && $channel !== '') {
            $this->sendRecoveredBalanceAlert($source, $balance, $threshold, $channel);
        }

        $monitor['low_balance_alert_sent'] = false;
        $monitor['low_balance_recovered_at'] = now()->toISOString();
        $settings['balance_monitor'] = $monitor;

        return $settings;
    }

    private function sendLowBalanceAlert(CaptchaSource $source, string $balance, float $threshold, string $channel): void
    {
        $title = sprintf('Nguồn captcha %s sắp hết số dư', $source->name);
        $payload = [
            'Source ID' => $source->id,
            'Nguồn' => $source->name,
            'Driver' => $source->driver,
            'Balance hiện tại' => $balance,
            'Ngưỡng cảnh báo' => $threshold,
        ];

        $this->sendChannelReport($channel, $title, $payload, false);
    }

    private function sendRecoveredBalanceAlert(CaptchaSource $source, string $balance, float $threshold, string $channel): void
    {
        $title = sprintf('Nguồn captcha %s đã hồi số dư', $source->name);
        $payload = [
            'Source ID' => $source->id,
            'Nguồn' => $source->name,
            'Driver' => $source->driver,
            'Balance hiện tại' => $balance,
            'Ngưỡng cảnh báo' => $threshold,
        ];

        $this->sendChannelReport($channel, $title, $payload, true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendChannelReport(string $channel, string $title, array $payload, bool $recovered): void
    {
        match ($channel) {
            'queue' => SendMessage::sendQueueReport($title, $payload),
            'info' => SendMessage::sendInfoReport($title, $payload),
            'ops' => SendMessage::sendOpsReport($title, $payload),
            'security' => SendMessage::sendSecurityReport($title, $payload),
            'alerts' => $recovered
                ? SendMessage::sendRecoveredReport($title, $payload)
                : SendMessage::sendAlertReport($title, $payload),
            'recovered' => SendMessage::sendRecoveredReport($title, $payload),
            'staging' => SendMessage::sendStagingReport($title, $payload),
            default => SendMessage::sendAlertReport($title, $payload),
        };
    }
}
