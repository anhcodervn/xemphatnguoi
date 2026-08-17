<?php

namespace App\Support;

use Illuminate\Support\Str;

class PublicSeoTextFiles
{
    public function __construct(private readonly SettingStore $settingStore) {}

    public function robots(): string
    {
        $fallback = (string) config('system_settings.defaults.seo.robots_txt', '');
        $configuredContent = $this->settingStore->getString('robots_txt', $fallback);
        $content = filled($configuredContent) ? $configuredContent : $fallback;

        $contentWithoutSitemap = Str::of($content)
            ->replace(["\r\n", "\r"], "\n")
            ->explode("\n")
            ->reject(fn (string $line): bool => Str::startsWith(Str::lower(trim($line)), 'sitemap:'))
            ->implode("\n");

        return $this->normalize($contentWithoutSitemap."\nSitemap: ".route('sitemap'));
    }

    public function ads(): string
    {
        return $this->normalize($this->settingStore->getString(
            'ads_txt',
            (string) config('system_settings.defaults.seo.ads_txt', ''),
        ));
    }

    private function normalize(string $content): string
    {
        $normalizedContent = Str::of($content)
            ->replace(["\r\n", "\r"], "\n")
            ->trim()
            ->toString();

        return $normalizedContent === '' ? '' : $normalizedContent."\n";
    }
}
