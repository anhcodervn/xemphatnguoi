<?php

namespace App\Features\TrafficFine\Requests;

use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\InvalidLicensePlateException;
use App\Features\TrafficFine\Services\LicensePlateNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TrafficFineLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(LicensePlateNormalizer $normalizer): array
    {
        return [
            'plate' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) use ($normalizer): void {
                    try {
                        $normalizer->normalize((string) $value);
                    } catch (InvalidLicensePlateException) {
                        $fail('Biển số xe không đúng định dạng hỗ trợ.');
                    }
                },
            ],
            'vehicle_type' => ['required', Rule::in(VehicleType::enabledValues())],
        ];
    }

    public function messages(): array
    {
        return [
            'plate.required' => 'Vui lòng nhập biển số xe.',
            'vehicle_type.required' => 'Vui lòng chọn loại phương tiện.',
            'vehicle_type.in' => 'Loại phương tiện không được hỗ trợ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'plate' => 'biển số xe',
            'vehicle_type' => 'loại phương tiện',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $status = $validator->errors()->has('vehicle_type') && ! $validator->errors()->has('plate')
            ? 'invalid_vehicle_type'
            : 'invalid_plate';

        throw new HttpResponseException(response()->json([
            'success' => false,
            'status' => $status,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
