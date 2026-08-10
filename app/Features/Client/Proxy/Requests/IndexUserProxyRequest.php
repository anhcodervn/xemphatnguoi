<?php

namespace App\Features\Client\Proxy\Requests;

use App\Exceptions\ApiException;
use App\Models\UserProxy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserProxyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', UserProxy::STATUS_ACTIVE, 'changing', 'expired', 'disabled', 'error'])],
            'protocol' => ['nullable', Rule::in(['http', 'https', 'socks4', 'socks5'])],
            'proxy_type' => ['nullable', Rule::in(['static', 'rotating'])],
            'country_code' => ['nullable', 'string', 'size:2'],
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'expiry'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }

    /** @return array<string, mixed> */
    public function validated($key = null, $default = null): array
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
