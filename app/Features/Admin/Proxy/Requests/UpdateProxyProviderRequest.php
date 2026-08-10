<?php

namespace App\Features\Admin\Proxy\Requests;

use App\Models\ProxyProvider;
use App\Rules\ValidProviderCredentials;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProxyProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash:ascii',
                Rule::unique('proxy_providers', 'code')->ignore($this->route('proxyProvider')),
            ],
            'order_method' => ['sometimes', 'required', Rule::in(ProxyProvider::ORDER_METHODS)],
            'credentials' => ['sometimes', new ValidProviderCredentials],
            'settings' => ['sometimes', 'nullable', 'array'],
            'settings.purchase_path' => ['nullable', 'string', 'max:255'],
            'settings.status_path' => ['nullable', 'string', 'max:255'],
            'settings.balance_path' => ['nullable', 'string', 'max:255'],
            'priority' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
