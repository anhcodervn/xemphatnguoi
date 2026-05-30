<?php

namespace App\Features\Admin\Setting\Controllers;

use App\Features\Admin\Setting\Requests\UpdateOptionSettingRequest;
use App\Features\Admin\Setting\Requests\UpdateSystemSettingRequest;
use App\Http\Controllers\Controller;
use App\Support\SettingStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SettingController extends Controller
{
    protected const SYSTEM_TAB = 'system';

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defaults(): array
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
                'color_primary' => '#0F172A',
                'color_accent' => '#0EA5E9',
                'color_surface' => '#F8FAFC',
            ],
            'home-category' => [
                'category_ids' => [],
            ],
            'slider-images' => [
                'items' => [],
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
            'options' => $this->defaultOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultOptions(): array
    {
        return [
            'terms_of_use' => [],
            'privacy_policy' => [],
            'refund_policy' => [],
            'recharge_syntax' => 'NAP',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSystem(): array
    {
        return [
            'site_name' => '',
            'site_domain' => '',
            'site_description' => '',
            'site_active' => true,
            'allow_register' => false,
            'support_email' => '',
            'hotline' => '',
            'address' => '',
            'facebook' => '',
            'zalo' => '',
            'youtube' => '',
            'meta_title' => '',
            'meta_description' => '',
            'robots' => 'index,follow',
            'gtm_id' => '',
            'meta_pixel_id' => '',
            'custom_script' => '',
            'recharge_syntax' => 'NAP',
            'terms_of_use' => [],
            'privacy_policy' => [],
            'refund_policy' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function systemFromLegacy(SettingStore $settingStore): array
    {
        $general = $settingStore->getArray('general', $this->defaults()['general']);
        $contact = $settingStore->getArray('contact', $this->defaults()['contact']);
        $seo = $settingStore->getArray('seo', $this->defaults()['seo']);
        $options = $settingStore->getArray('options', $this->defaultOptions());
        $rechargeSyntax = trim($settingStore->getString('recharge_syntax', (string) ($options['recharge_syntax'] ?? 'NAP')));

        return [
            'site_name' => (string) ($general['site_name'] ?? ''),
            'site_domain' => (string) ($general['site_domain'] ?? ''),
            'site_description' => (string) ($general['site_description'] ?? ''),
            'site_active' => (bool) ($general['site_active'] ?? true),
            'allow_register' => (bool) ($general['allow_register'] ?? false),
            'support_email' => (string) ($contact['support_email'] ?? ''),
            'hotline' => (string) ($contact['hotline'] ?? ''),
            'address' => (string) ($contact['address'] ?? ''),
            'facebook' => (string) ($contact['facebook'] ?? ''),
            'zalo' => (string) ($contact['zalo'] ?? ''),
            'youtube' => (string) ($contact['youtube'] ?? ''),
            'meta_title' => (string) ($seo['meta_title'] ?? ''),
            'meta_description' => (string) ($seo['meta_description'] ?? ''),
            'robots' => (string) ($seo['robots'] ?? 'index,follow'),
            'gtm_id' => (string) ($seo['gtm_id'] ?? ''),
            'meta_pixel_id' => (string) ($seo['meta_pixel_id'] ?? ''),
            'custom_script' => (string) ($seo['custom_script'] ?? ''),
            'recharge_syntax' => $rechargeSyntax !== '' ? $rechargeSyntax : 'NAP',
            'terms_of_use' => is_array($options['terms_of_use'] ?? null) ? $options['terms_of_use'] : [],
            'privacy_policy' => is_array($options['privacy_policy'] ?? null) ? $options['privacy_policy'] : [],
            'refund_policy' => is_array($options['refund_policy'] ?? null) ? $options['refund_policy'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function syncLegacyFromSystem(array $payload, SettingStore $settingStore): void
    {
        $settingStore->putArray('general', [
            'site_name' => $payload['site_name'],
            'site_domain' => $payload['site_domain'],
            'site_description' => $payload['site_description'],
            'site_active' => $payload['site_active'],
            'allow_register' => $payload['allow_register'],
        ]);

        $settingStore->putArray('contact', [
            'hotline' => $payload['hotline'],
            'support_email' => $payload['support_email'],
            'address' => $payload['address'],
            'facebook' => $payload['facebook'],
            'zalo' => $payload['zalo'],
            'youtube' => $payload['youtube'],
        ]);

        $settingStore->putArray('seo', [
            'meta_title' => $payload['meta_title'],
            'meta_description' => $payload['meta_description'],
            'robots' => $payload['robots'],
            'gtm_id' => $payload['gtm_id'],
            'meta_pixel_id' => $payload['meta_pixel_id'],
            'custom_script' => $payload['custom_script'],
        ]);

        $settingStore->putArray('options', [
            'terms_of_use' => $payload['terms_of_use'],
            'privacy_policy' => $payload['privacy_policy'],
            'refund_policy' => $payload['refund_policy'],
            'recharge_syntax' => $payload['recharge_syntax'],
        ]);
        $settingStore->putString('recharge_syntax', (string) $payload['recharge_syntax']);
    }

    public function show(string $tab, SettingStore $settingStore): JsonResponse
    {
        if ($tab === self::SYSTEM_TAB) {
            $settings = [
                ...$this->defaultSystem(),
                ...$this->systemFromLegacy($settingStore),
                ...$settingStore->getArray(self::SYSTEM_TAB, []),
            ];

            return response()->json([
                'status' => true,
                'data' => [
                    'tab' => $tab,
                    'settings' => $settings,
                ],
            ]);
        }

        $defaults = $this->defaults()[$tab] ?? null;
        abort_if($defaults === null, 404);

        $settings = $settingStore->getArray($tab, $defaults);

        return response()->json([
            'status' => true,
            'data' => [
                'tab' => $tab,
                'settings' => [
                    ...$defaults,
                    ...$settings,
                ],
            ],
        ]);
    }

    public function update(string $tab, Request $request, SettingStore $settingStore): JsonResponse
    {
        if ($tab === self::SYSTEM_TAB) {
            abort(404);
        }

        abort_if($tab === 'options', 404);

        $defaults = $this->defaults()[$tab] ?? null;
        abort_if($defaults === null, 404);

        /** @var array<string, mixed> $payload */
        $payload = [
            ...$defaults,
            ...$request->all(),
        ];

        $settingStore->putArray($tab, $payload);

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

        $settingStore->putArray(self::SYSTEM_TAB, $payload);
        $this->syncLegacyFromSystem($payload, $settingStore);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật cấu hình hệ thống thành công.',
            'data' => [
                'tab' => self::SYSTEM_TAB,
                'settings' => $payload,
            ],
        ]);
    }

    public function updateOptions(UpdateOptionSettingRequest $request, SettingStore $settingStore): JsonResponse
    {
        $payload = [
            ...$this->defaultOptions(),
            ...Arr::only($request->validated(), ['terms_of_use', 'privacy_policy', 'refund_policy', 'recharge_syntax']),
        ];

        $payload['recharge_syntax'] = trim((string) $payload['recharge_syntax']) ?: 'NAP';

        $settingStore->putArray('options', $payload);
        $settingStore->putString('recharge_syntax', (string) $payload['recharge_syntax']);

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
