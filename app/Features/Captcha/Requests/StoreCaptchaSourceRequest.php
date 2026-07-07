<?php

namespace App\Features\Captcha\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaptchaSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', 'max:100'],
            'api_base_url' => ['nullable', 'string', 'max:255'],
            'credentials' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
