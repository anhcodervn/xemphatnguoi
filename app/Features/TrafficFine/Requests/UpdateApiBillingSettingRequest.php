<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApiBillingSettingRequest extends FormRequest
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
            'api_request_price' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'api_request_price.required' => 'Vui lòng nhập giá mỗi lượt tra cứu API.',
            'api_request_price.integer' => 'Giá mỗi lượt tra cứu API phải là số nguyên.',
            'api_request_price.min' => 'Giá mỗi lượt tra cứu API phải ít nhất 1 đồng.',
            'api_request_price.max' => 'Giá mỗi lượt tra cứu API không được vượt quá 1.000.000 đồng.',
        ];
    }

    public function attributes(): array
    {
        return [
            'api_request_price' => 'giá mỗi lượt tra cứu API',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
