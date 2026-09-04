<?php

namespace App\Features\Admin\Setting\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateMonitoringSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'interval_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }

    public function messages(): array
    {
        return [
            'interval_hours.required' => 'Vui lòng nhập chu kỳ theo dõi.',
            'interval_hours.integer' => 'Chu kỳ theo dõi phải là số giờ nguyên.',
            'interval_hours.min' => 'Chu kỳ theo dõi tối thiểu là 1 giờ.',
            'interval_hours.max' => 'Chu kỳ theo dõi tối đa là 720 giờ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
            'data' => ['errors' => $validator->errors()],
        ], 422));
    }
}
