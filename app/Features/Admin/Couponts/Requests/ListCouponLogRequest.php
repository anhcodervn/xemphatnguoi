<?php

namespace App\Features\Admin\Couponts\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ListCouponLogRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:success,failed,info'],
            'action' => ['nullable', 'string', 'max:50'],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'search' => 'từ khóa',
            'status' => 'trạng thái log',
            'action' => 'hành động',
            'coupon_id' => 'mã giảm giá',
            'user_id' => 'người dùng',
            'per_page' => 'số dòng',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
