<?php

namespace App\Features\Admin\Couponts\Requests;

use App\Exceptions\ApiException;
use App\Models\Coupon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
        /** @var Coupon|null $coupon */
        $coupon = $this->route('coupon');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'string', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_usage' => ['nullable', 'integer', 'min:1'],
            'max_usage_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after:starts_at'],
            'first_order_only' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'applicable_package_ids' => ['nullable', 'array'],
            'applicable_package_ids.*' => ['integer', 'exists:packages,id'],
            'requirements' => ['nullable', 'array'],
            'requirements.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'mã coupon',
            'name' => 'tên coupon',
            'description' => 'mô tả',
            'type' => 'loại coupon',
            'value' => 'giá trị giảm',
            'min_order_amount' => 'đơn tối thiểu',
            'max_discount_amount' => 'giảm tối đa',
            'max_usage' => 'số lượt dùng tối đa',
            'max_usage_per_user' => 'số lượt dùng tối đa mỗi user',
            'starts_at' => 'ngày bắt đầu',
            'expired_at' => 'ngày hết hạn',
            'first_order_only' => 'chỉ áp dụng đơn đầu',
            'is_active' => 'trạng thái',
            'applicable_package_ids' => 'gói áp dụng',
            'requirements.note' => 'ghi chú điều kiện',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'first_order_only' => $this->boolean('first_order_only'),
            'is_active' => $this->boolean('is_active', true),
            'min_order_amount' => $this->input('min_order_amount') ?? 0,
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
