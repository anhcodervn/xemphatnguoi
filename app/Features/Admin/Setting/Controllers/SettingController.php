<?php

namespace App\Features\Admin\Setting\Controllers;

use App\Features\Admin\Setting\Requests\UpdateOptionSettingRequest;
use App\Features\Admin\Setting\Requests\UpdateSystemSettingRequest;
use App\Features\Admin\Setting\Requests\UpdateTabSettingRequest;
use App\Http\Controllers\Controller;
use App\Support\SettingStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class SettingController extends Controller
{
    protected const SYSTEM_TAB = 'system';

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function tabDefaults(): array
    {
        return [
            'general' => [
                'site_name' => '',
                'site_domain' => '',
                'site_description' => '',
                'site_active' => true,
                'allow_register' => false,
            ],
            'branding' => [
                'logo' => '',
                'favicon' => '',
                'og_image' => '',
                'color_primary' => '#0F172A',
                'color_accent' => '#2563EB',
                'color_surface' => '#F8FAFC',
            ],
            'contact' => [
                'hotline' => '',
                'support_email' => '',
                'address' => '',
                'facebook' => '',
                'zalo' => '',
                'youtube' => '',
            ],
            'seo' => [
                'meta_title' => '',
                'meta_description' => '',
                'robots' => 'index,follow',
                'gtm_id' => '',
                'meta_pixel_id' => '',
                'custom_script' => '',
            ],
            'options' => [
                'terms_of_use' => [],
                'privacy_policy' => [],
                'refund_policy' => [],
                'recharge_syntax' => 'NAP',
            ],
            'home-category' => [
                'category_ids' => [],
            ],
            'slider-images' => [
                'items' => [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSystem(): array
    {
        return [
            ...$this->tabDefaults()['general'],
            ...$this->tabDefaults()['branding'],
            ...$this->tabDefaults()['contact'],
            ...$this->tabDefaults()['seo'],
            ...$this->tabDefaults()['options'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function tabStorageMap(): array
    {
        return [
            'general' => [
                'site_name' => 'site_name',
                'site_domain' => 'site_domain',
                'site_description' => 'site_description',
                'site_active' => 'site_active',
                'allow_register' => 'allow_register',
            ],
            'branding' => [
                'logo' => 'logo',
                'favicon' => 'favicon',
                'og_image' => 'og_image',
                'color_primary' => 'color_primary',
                'color_accent' => 'color_accent',
                'color_surface' => 'color_surface',
            ],
            'contact' => [
                'hotline' => 'hotline',
                'support_email' => 'support_email',
                'address' => 'address',
                'facebook' => 'facebook',
                'zalo' => 'zalo',
                'youtube' => 'youtube',
            ],
            'seo' => [
                'meta_title' => 'meta_title',
                'meta_description' => 'meta_description',
                'robots' => 'robots',
                'gtm_id' => 'gtm_id',
                'meta_pixel_id' => 'meta_pixel_id',
                'custom_script' => 'custom_script',
            ],
            'options' => [
                'terms_of_use' => 'terms_of_use',
                'privacy_policy' => 'privacy_policy',
                'refund_policy' => 'refund_policy',
                'recharge_syntax' => 'recharge_syntax',
            ],
            'home-category' => [
                'category_ids' => 'home_category_ids',
            ],
            'slider-images' => [
                'items' => 'home_slider_items',
            ],
        ];
    }

    protected function tabExists(string $tab): bool
    {
        return array_key_exists($tab, $this->tabDefaults());
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, string>  $storageMap
     * @return array<string, mixed>
     */
    protected function readTab(SettingStore $settingStore, array $defaults, array $storageMap): array
    {
        $storedValues = $settingStore->getMany(
            collect($storageMap)->mapWithKeys(fn (string $storageKey, string $field) => [$storageKey => $defaults[$field]])->all(),
        );

        $resolved = [];

        foreach ($storageMap as $field => $storageKey) {
            $resolved[$field] = $storedValues[$storageKey] ?? $defaults[$field];
        }

        return [
            ...$defaults,
            ...$resolved,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $storageMap
     */
    protected function writeTab(SettingStore $settingStore, array $payload, array $storageMap): void
    {
        $settingStore->putMany(
            collect($storageMap)->mapWithKeys(fn (string $storageKey, string $field) => [$storageKey => $payload[$field] ?? null])->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function systemSettings(SettingStore $settingStore): array
    {
        $general = $this->readTab($settingStore, $this->tabDefaults()['general'], $this->tabStorageMap()['general']);
        $branding = $this->readTab($settingStore, $this->tabDefaults()['branding'], $this->tabStorageMap()['branding']);
        $contact = $this->readTab($settingStore, $this->tabDefaults()['contact'], $this->tabStorageMap()['contact']);
        $seo = $this->readTab($settingStore, $this->tabDefaults()['seo'], $this->tabStorageMap()['seo']);
        $options = $this->readTab($settingStore, $this->tabDefaults()['options'], $this->tabStorageMap()['options']);

        return [
            ...$this->defaultSystem(),
            ...$general,
            ...$branding,
            ...$contact,
            ...$seo,
            ...$options,
        ];
    }

    public function show(string $tab, SettingStore $settingStore): JsonResponse
    {
        if ($tab === self::SYSTEM_TAB) {
            return response()->json([
                'status' => true,
                'data' => [
                    'tab' => $tab,
                    'settings' => $this->systemSettings($settingStore),
                ],
            ]);
        }

        abort_if(! $this->tabExists($tab), 404);

        $settings = $this->readTab($settingStore, $this->tabDefaults()[$tab], $this->tabStorageMap()[$tab]);

        return response()->json([
            'status' => true,
            'data' => [
                'tab' => $tab,
                'settings' => $settings,
            ],
        ]);
    }

    public function update(string $tab, UpdateTabSettingRequest $request, SettingStore $settingStore): JsonResponse
    {
        abort_if($tab === self::SYSTEM_TAB || $tab === 'options', 404);
        abort_if(! $this->tabExists($tab), 404);

        $payload = [
            ...$this->tabDefaults()[$tab],
            ...$request->validated(),
        ];

        $this->writeTab($settingStore, $payload, $this->tabStorageMap()[$tab]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật cấu hình thành công.',
            'data' => [
                'tab' => $tab,
                'settings' => $payload,
            ],
        ]);
    }

    public function updateSystem(UpdateSystemSettingRequest $request, SettingStore $settingStore): JsonResponse
    {
        $payload = [
            ...$this->defaultSystem(),
            ...$request->validated(),
        ];

        $payload['recharge_syntax'] = trim((string) $payload['recharge_syntax']) ?: 'NAP';

        foreach (['general', 'branding', 'contact', 'seo', 'options'] as $tab) {
            $tabPayload = Arr::only($payload, array_keys($this->tabDefaults()[$tab]));
            $this->writeTab($settingStore, $tabPayload, $this->tabStorageMap()[$tab]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật cấu hình hệ thống thành công.',
            'data' => [
                'tab' => self::SYSTEM_TAB,
                'settings' => $this->systemSettings($settingStore),
            ],
        ]);
    }

    public function updateOptions(UpdateOptionSettingRequest $request, SettingStore $settingStore): JsonResponse
    {
        $payload = [
            ...$this->tabDefaults()['options'],
            ...Arr::only($request->validated(), ['terms_of_use', 'privacy_policy', 'refund_policy', 'recharge_syntax']),
        ];

        $payload['recharge_syntax'] = trim((string) $payload['recharge_syntax']) ?: 'NAP';

        $this->writeTab($settingStore, $payload, $this->tabStorageMap()['options']);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật cấu hình nạp tiền thành công.',
            'data' => [
                'tab' => 'options',
                'settings' => $payload,
            ],
        ]);
    }
}
