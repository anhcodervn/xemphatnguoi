<?php

namespace App\Features\Admin\Setting\Requests;

use App\Features\TrafficFine\Services\TrafficFineTurnstileSettingsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTurnstileSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(TrafficFineTurnstileSettingsService $settings): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'site_key' => [
                Rule::requiredIf(fn (): bool => $this->boolean('enabled')),
                'nullable',
                'string',
                'max:255',
            ],
            'secret_key' => [
                Rule::requiredIf(fn (): bool => $this->boolean('enabled') && ! $settings->hasSecret()),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'site_key.required' => 'Vui lòng nhập Site key trước khi bật Cloudflare Turnstile.',
            'secret_key.required' => 'Vui lòng nhập Secret key trước khi bật Cloudflare Turnstile.',
        ];
    }

    public function attributes(): array
    {
        return [
            'enabled' => 'trạng thái Turnstile',
            'site_key' => 'Site key',
            'secret_key' => 'Secret key',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
            'data' => [
                'errors' => $validator->errors(),
            ],
        ], 422));
    }
}
