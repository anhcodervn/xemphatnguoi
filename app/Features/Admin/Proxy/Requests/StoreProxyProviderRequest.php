<?php

namespace App\Features\Admin\Proxy\Requests;

use App\Models\ProxyProvider;
use App\Rules\ValidProviderCredentials;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProxyProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('proxy_providers', 'code')],
            'order_method' => ['required', Rule::in(ProxyProvider::ORDER_METHODS)],
            'credentials' => ['nullable', new ValidProviderCredentials],
            'settings' => ['nullable', 'array'],
            'settings.purchase_path' => ['nullable', 'string', 'max:255'],
            'settings.status_path' => ['nullable', 'string', 'max:255'],
            'settings.balance_path' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
