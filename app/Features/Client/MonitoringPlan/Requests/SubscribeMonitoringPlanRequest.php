<?php

namespace App\Features\Client\MonitoringPlan\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeMonitoringPlanRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', 'exists:monitoring_plans,id'],
            'vehicle_count' => ['nullable', 'integer', 'min:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_count.min' => 'Số lượng xe tùy chỉnh phải từ 20 xe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'plan_id' => 'gói theo dõi',
            'vehicle_count' => 'số lượng xe',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
