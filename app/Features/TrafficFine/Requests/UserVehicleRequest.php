<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\InvalidLicensePlateException;
use App\Features\TrafficFine\Services\LicensePlateNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserVehicleRequest extends FormRequest
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
        $vehicleId = $this->route('vehicle')?->id ?? $this->route('vehicle');
        $normalizedPlate = (string) $this->input('plate');

        try {
            $normalizedPlate = $normalizer->normalize($normalizedPlate);
            $this->merge(['plate' => $normalizedPlate]);
        } catch (InvalidLicensePlateException) {
        }

        return [
            'name' => ['required', 'string', 'max:100'],
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
                Rule::unique('user_vehicles', 'plate')
                    ->where('user_id', $this->user()?->id)
                    ->where('vehicle_type', (string) $this->input('vehicle_type'))
                    ->ignore($vehicleId),
            ],
            'vehicle_type' => ['required', Rule::in(VehicleType::enabledValues())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên gợi nhớ cho xe.',
            'plate.required' => 'Vui lòng nhập biển số xe.',
            'plate.unique' => 'Biển số này đã có trong garage của bạn.',
            'vehicle_type.in' => 'Loại phương tiện không được hỗ trợ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên xe',
            'plate' => 'biển số xe',
            'vehicle_type' => 'loại phương tiện',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
