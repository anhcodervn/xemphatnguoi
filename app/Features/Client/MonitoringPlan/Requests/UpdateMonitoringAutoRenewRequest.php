<?php

namespace App\Features\Client\MonitoringPlan\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitoringAutoRenewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'auto_renew' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'auto_renew.required' => 'Vui lòng chọn trạng thái tự gia hạn.',
            'auto_renew.boolean' => 'Trạng thái tự gia hạn không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'auto_renew' => 'tự gia hạn',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
