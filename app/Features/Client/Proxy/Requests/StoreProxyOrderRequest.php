<?php

namespace App\Features\Client\Proxy\Requests;

use App\Models\ProxyProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProxyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_code' => trim((string) $this->input('product_code')),
            'protocol' => mb_strtolower(trim((string) $this->input('protocol'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_code' => ['required', 'string', 'max:100', 'alpha_dash:ascii'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'protocol' => ['required', 'string', Rule::in(ProxyProduct::SUPPORTED_PROTOCOLS)],
            'idempotency_key' => ['prohibited'],
            'idempotencyKey' => ['prohibited'],
        ];
    }
}
