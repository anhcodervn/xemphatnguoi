<?php

namespace App\Features\TrafficFine\Services;

use App\Models\User;
use App\Support\SettingStore;

class TrafficFineTurnstileSettingsService
{
    public const ENABLED_KEY = 'traffic_fine_turnstile_enabled';

    public const SITE_KEY = 'traffic_fine_turnstile_site_key';

    public const SECRET_KEY = 'traffic_fine_turnstile_secret_key';

    public function __construct(
        private readonly SettingStore $settingStore,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settingStore->get(
            self::ENABLED_KEY,
            (bool) config('services.turnstile.enabled', false),
        );
    }

    public function siteKey(): string
    {
        return trim($this->settingStore->getString(
            self::SITE_KEY,
            (string) config('services.turnstile.site_key', ''),
        ));
    }

    public function secretKey(): string
    {
        return trim($this->settingStore->getEncryptedString(
            self::SECRET_KEY,
            (string) config('services.turnstile.secret_key', ''),
        ));
    }

    public function isConfigured(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function hasSecret(): bool
    {
        return $this->secretKey() !== '';
    }

    public function requiresChallenge(?User $user): bool
    {
        return $this->isEnabled() && (! $user instanceof User || $user->role !== 'admin');
    }

    /**
     * @return array{required: bool, available: bool, site_key: string}
     */
    public function publicConfiguration(?User $user): array
    {
        $required = $this->requiresChallenge($user);

        return [
            'required' => $required,
            'available' => ! $required || $this->isConfigured(),
            'site_key' => $required ? $this->siteKey() : '',
        ];
    }

    /**
     * @return array{enabled: bool, site_key: string, secret_configured: bool}
     */
    public function adminConfiguration(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'site_key' => $this->siteKey(),
            'secret_configured' => $this->hasSecret(),
        ];
    }

    public function update(bool $enabled, string $siteKey, ?string $secretKey = null): void
    {
        $this->settingStore->putMany([
            self::ENABLED_KEY => $enabled,
            self::SITE_KEY => trim($siteKey),
        ]);

        if (filled($secretKey)) {
            $this->settingStore->putEncryptedString(self::SECRET_KEY, trim((string) $secretKey));
        }
    }
}
