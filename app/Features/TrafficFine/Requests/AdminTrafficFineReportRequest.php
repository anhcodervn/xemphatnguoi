<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminTrafficFineReportRequest extends FormRequest
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
            'days' => ['nullable', 'integer', Rule::in([7, 30, 90])],
        ];
    }

    public function messages(): array
    {
        return [
            'days.in' => 'Khoảng báo cáo chỉ hỗ trợ 7, 30 hoặc 90 ngày.',
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
