<?php

namespace App\Features\Admin\Proxy\Requests;

use App\Features\Admin\Proxy\Requests\Concerns\ValidatesProxyProductPricing;
use App\Models\ProxyProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProxyProductRequest extends FormRequest
{
    use ValidatesProxyProductPricing;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proxy_category_id' => ['required', 'integer', 'exists:proxy_categories,id'],
            'code' => ['required', 'string', 'max:100', Rule::unique('proxy_products', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'supported_protocols' => ['required', 'array', 'min:1', 'max:4'],
            'supported_protocols.*' => ['required', 'string', 'distinct', Rule::in(ProxyProduct::SUPPORTED_PROTOCOLS)],
            'description' => ['nullable', 'string'],
            'provider_product_code' => ['nullable', 'string', 'max:255'],
            'default_provider_id' => ['nullable', 'integer', 'exists:proxy_providers,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'max_quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
            'settings.proxy_type' => ['nullable', 'string', Rule::in(['static', 'rotating'])],
            'settings.rotating_carrier' => ['nullable', 'string', 'max:50'],
            'settings.rotating_province' => ['nullable', 'string', 'max:20'],
            'settings.rotating_whitelist' => ['nullable', 'string', 'max:255'],
        ];
    }
}
