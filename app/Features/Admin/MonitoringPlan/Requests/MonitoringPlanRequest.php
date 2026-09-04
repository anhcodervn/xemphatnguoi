<?php

namespace App\Features\Admin\MonitoringPlan\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class MonitoringPlanRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:20000'],
            'is_custom' => ['required', 'boolean'],
            'vehicle_limit' => ['nullable', 'required_if:is_custom,false', 'integer', 'min:1'],
            'price' => ['nullable', 'required_if:is_custom,false', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'required_if:is_custom,true', 'numeric', 'min:0'],
            'min_vehicle_count' => ['nullable', 'required_if:is_custom,true', 'integer', 'min:20'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_limit.required_if' => 'Vui lòng nhập số xe tối đa của gói cố định.',
            'price.required_if' => 'Vui lòng nhập giá của gói cố định.',
            'unit_price.required_if' => 'Vui lòng nhập đơn giá cho một xe.',
            'min_vehicle_count.required_if' => 'Vui lòng nhập số xe tối thiểu của gói tùy chỉnh.',
            'min_vehicle_count.min' => 'Số xe tối thiểu của gói tùy chỉnh phải từ 20 xe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên gói',
            'vehicle_limit' => 'giới hạn xe',
            'price' => 'giá gói',
            'unit_price' => 'đơn giá một xe',
            'min_vehicle_count' => 'số xe tối thiểu',
            'duration_days' => 'thời hạn gói',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
