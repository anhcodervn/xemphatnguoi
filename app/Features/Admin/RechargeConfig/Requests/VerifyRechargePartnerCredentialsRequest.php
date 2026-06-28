<?php

namespace App\Features\Admin\RechargeConfig\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRechargePartnerCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'max:120'],
            'api_secret' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'api_key' => trim((string) $this->input('api_key')),
            'api_secret' => trim((string) $this->input('api_secret')),
        ]);
    }

    public function attributes(): array
    {
        return [
            'api_key' => 'API key',
            'api_secret' => 'API secret',
        ];
    }
}
