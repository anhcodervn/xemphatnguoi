<?php

namespace App\Features\Admin\Setting\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOptionSettingRequest extends FormRequest
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
            'terms_of_use' => ['nullable', 'array'],
            'privacy_policy' => ['nullable', 'array'],
            'refund_policy' => ['nullable', 'array'],
            'recharge_syntax' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'terms_of_use' => 'điều khoản sử dụng',
            'privacy_policy' => 'chính sách bảo mật',
            'refund_policy' => 'chính sách hoàn tiền',
            'recharge_syntax' => 'cú pháp nạp tiền',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
