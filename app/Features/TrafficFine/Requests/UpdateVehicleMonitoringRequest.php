<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleMonitoringRequest extends FormRequest
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
            'enabled' => ['required', 'boolean'],
            'email_notifications' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'enabled.required' => 'Vui lòng chọn trạng thái theo dõi.',
            'enabled.boolean' => 'Trạng thái theo dõi không hợp lệ.',
            'email_notifications.required' => 'Vui lòng chọn trạng thái thông báo email.',
            'email_notifications.boolean' => 'Trạng thái thông báo email không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'enabled' => 'trạng thái theo dõi',
            'email_notifications' => 'thông báo email',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
