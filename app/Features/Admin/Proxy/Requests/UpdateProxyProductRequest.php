<?php

namespace App\Features\Admin\Proxy\Requests;

use App\Features\Admin\Proxy\Requests\Concerns\ValidatesProxyProductPricing;
use App\Models\ProxyProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProxyProductRequest extends FormRequest
{
    use ValidatesProxyProductPricing;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('proxyProduct')?->getKey();

        return [
            'proxy_category_id' => ['sometimes', 'required', 'integer', 'exists:proxy_categories,id'],
            'code' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('proxy_products', 'code')->ignore($productId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'supported_protocols' => ['sometimes', 'required', 'array', 'min:1', 'max:4'],
            'supported_protocols.*' => ['required', 'string', 'distinct', Rule::in(ProxyProduct::SUPPORTED_PROTOCOLS)],
            'description' => ['sometimes', 'nullable', 'string'],
            'provider_product_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'default_provider_id' => ['sometimes', 'nullable', 'integer', 'exists:proxy_providers,id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'max_quantity' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'settings.proxy_type' => ['sometimes', 'nullable', 'string', Rule::in(['static', 'rotating'])],
            'settings.rotating_carrier' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.rotating_province' => ['sometimes', 'nullable', 'string', 'max:20'],
            'settings.rotating_whitelist' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
