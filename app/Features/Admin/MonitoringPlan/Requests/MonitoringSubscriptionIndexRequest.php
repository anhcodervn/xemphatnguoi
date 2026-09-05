<?php

namespace App\Features\Admin\MonitoringPlan\Requests;

use App\Exceptions\ApiException;
use App\Models\MonitoringSubscription;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitoringSubscriptionIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                MonitoringSubscription::STATUS_ACTIVE,
                MonitoringSubscription::STATUS_RENEWED,
                MonitoringSubscription::STATUS_UPGRADED,
                'expired',
            ])],
            'auto_renew' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Trạng thái gói đã thuê không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'search' => 'từ khóa',
            'status' => 'trạng thái',
            'auto_renew' => 'tự động gia hạn',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422, [
            'errors' => $validator->errors(),
        ]);
    }
}
