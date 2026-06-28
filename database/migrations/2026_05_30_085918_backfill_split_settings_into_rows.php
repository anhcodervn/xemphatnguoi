<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('settings')->get()->keyBy('key');

        $general = $this->decodeRow($rows->get('general'));
        $branding = $this->decodeRow($rows->get('branding'));
        $contact = $this->decodeRow($rows->get('contact'));
        $seo = $this->decodeRow($rows->get('seo'));
        $system = $this->decodeRow($rows->get('system'));
        $options = $this->decodeRow($rows->get('options'));
        $homeCategory = $this->decodeRow($rows->get('home-category'));
        $sliderImages = $this->decodeRow($rows->get('slider-images'));

        $payload = [
            'site_name' => $system['site_name'] ?? $general['site_name'] ?? '',
            'site_domain' => $system['site_domain'] ?? $general['site_domain'] ?? '',
            'site_description' => $system['site_description'] ?? $general['site_description'] ?? '',
            'site_active' => (bool) ($system['site_active'] ?? $general['site_active'] ?? true),
            'allow_register' => (bool) ($system['allow_register'] ?? $general['allow_register'] ?? false),
            'logo' => $branding['logo'] ?? '',
            'favicon' => $branding['favicon'] ?? '',
            'og_image' => $branding['og_image'] ?? '',
            'color_primary' => $branding['color_primary'] ?? '#0F172A',
            'color_accent' => $branding['color_accent'] ?? '#2563EB',
            'color_surface' => $branding['color_surface'] ?? '#F8FAFC',
            'hotline' => $system['hotline'] ?? $contact['hotline'] ?? '',
            'support_email' => $system['support_email'] ?? $contact['support_email'] ?? '',
            'address' => $system['address'] ?? $contact['address'] ?? '',
            'facebook' => $system['facebook'] ?? $contact['facebook'] ?? '',
            'zalo' => $system['zalo'] ?? $contact['zalo'] ?? '',
            'youtube' => $system['youtube'] ?? $contact['youtube'] ?? '',
            'meta_title' => $system['meta_title'] ?? $seo['meta_title'] ?? '',
            'meta_description' => $system['meta_description'] ?? $seo['meta_description'] ?? '',
            'robots' => $system['robots'] ?? $seo['robots'] ?? 'index,follow',
            'gtm_id' => $system['gtm_id'] ?? $seo['gtm_id'] ?? '',
            'meta_pixel_id' => $system['meta_pixel_id'] ?? $seo['meta_pixel_id'] ?? '',
            'custom_script' => $system['custom_script'] ?? $seo['custom_script'] ?? '',
            'terms_of_use' => $system['terms_of_use'] ?? $options['terms_of_use'] ?? [],
            'privacy_policy' => $system['privacy_policy'] ?? $options['privacy_policy'] ?? [],
            'refund_policy' => $system['refund_policy'] ?? $options['refund_policy'] ?? [],
            'home_category_ids' => $homeCategory['category_ids'] ?? [],
            'home_slider_items' => $sliderImages['items'] ?? [],
        ];

        foreach ($payload as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $this->encodeValue($value),
                    'type' => $this->detectType($value),
                    'updated_at' => now(),
                    'created_at' => optional($rows->get($key))->created_at ?? now(),
                ],
            );
        }

        DB::table('settings')
            ->whereIn('key', ['system', 'general', 'branding', 'contact', 'seo', 'options', 'home-category', 'slider-images'])
            ->delete();
    }

    public function down(): void
    {
        $rows = DB::table('settings')->get()->keyBy('key');

        $general = Arr::only($this->readRows($rows, [
            'site_name',
            'site_domain',
            'site_description',
            'site_active',
            'allow_register',
        ]), ['site_name', 'site_domain', 'site_description', 'site_active', 'allow_register']);

        $branding = Arr::only($this->readRows($rows, [
            'logo',
            'favicon',
            'og_image',
            'color_primary',
            'color_accent',
            'color_surface',
        ]), ['logo', 'favicon', 'og_image', 'color_primary', 'color_accent', 'color_surface']);

        $contact = Arr::only($this->readRows($rows, [
            'hotline',
            'support_email',
            'address',
            'facebook',
            'zalo',
            'youtube',
        ]), ['hotline', 'support_email', 'address', 'facebook', 'zalo', 'youtube']);

        $seo = Arr::only($this->readRows($rows, [
            'meta_title',
            'meta_description',
            'robots',
            'gtm_id',
            'meta_pixel_id',
            'custom_script',
        ]), ['meta_title', 'meta_description', 'robots', 'gtm_id', 'meta_pixel_id', 'custom_script']);

        $options = Arr::only($this->readRows($rows, [
            'terms_of_use',
            'privacy_policy',
            'refund_policy',
        ]), ['terms_of_use', 'privacy_policy', 'refund_policy']);

        $system = [
            ...$general,
            ...$contact,
            ...$seo,
            ...$options,
        ];

        $legacyRows = [
            'system' => $system,
            'general' => $general,
            'branding' => $branding,
            'contact' => $contact,
            'seo' => $seo,
            'options' => $options,
            'home-category' => [
                'category_ids' => $this->decodeSettingValue($rows->get('home_category_ids'), []),
            ],
            'slider-images' => [
                'items' => $this->decodeSettingValue($rows->get('home_slider_items'), []),
            ],
        ];

        foreach ($legacyRows as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'type' => 'json',
                    'updated_at' => now(),
                    'created_at' => optional($rows->get($key))->created_at ?? now(),
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeRow(?object $row): array
    {
        if ($row === null || $row->type !== 'json') {
            return [];
        }

        $decoded = json_decode((string) $row->value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function detectType(mixed $value): string
    {
        if (is_array($value)) {
            return 'json';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        return 'string';
    }

    protected function encodeValue(mixed $value): ?string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value === null ? null : (string) $value;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    protected function readRows(Collection $rows, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->decodeSettingValue($rows->get($key));
        }

        return $values;
    }

    protected function decodeSettingValue(?object $row, mixed $default = null): mixed
    {
        if ($row === null) {
            return $default;
        }

        if ($row->type === 'json') {
            $decoded = json_decode((string) $row->value, true);

            return is_array($decoded) ? $decoded : $default;
        }

        if ($row->type === 'boolean') {
            return filter_var($row->value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
        }

        return $row->value ?? $default;
    }
};
