<?php

namespace App\Features\Admin\Setting\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOptionSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'recharge_syntax' => ['required', 'string', 'max:50'],
            'terms_of_use' => ['nullable', 'array'],
            'privacy_policy' => ['nullable', 'array'],
            'refund_policy' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recharge_syntax' => 'cú pháp nạp tiền',
            'terms_of_use' => 'điều khoản sử dụng',
            'privacy_policy' => 'chính sách bảo mật',
            'refund_policy' => 'chính sách hoàn tiền',
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
