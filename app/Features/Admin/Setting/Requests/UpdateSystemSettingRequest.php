<?php

namespace App\Features\Admin\Setting\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:190'],
            'site_domain' => ['nullable', 'string', 'max:190'],
            'site_description' => ['nullable', 'string', 'max:2000'],
            'site_active' => ['required', 'boolean'],
            'allow_register' => ['required', 'boolean'],
            'support_email' => ['nullable', 'email', 'max:190'],
            'hotline' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'zalo' => ['nullable', 'string', 'max:255'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'robots' => ['nullable', 'string', 'max:100'],
            'gtm_id' => ['nullable', 'string', 'max:100'],
            'meta_pixel_id' => ['nullable', 'string', 'max:100'],
            'custom_script' => ['nullable', 'string'],
            'recharge_syntax' => ['required', 'string', 'max:50'],
            'terms_of_use' => ['nullable', 'array'],
            'privacy_policy' => ['nullable', 'array'],
            'refund_policy' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'site_name.required' => 'Vui lòng nhập tên hệ thống.',
            'support_email.email' => 'Email hỗ trợ không đúng định dạng.',
            'recharge_syntax.required' => 'Vui lòng nhập cú pháp nạp.',
        ];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
