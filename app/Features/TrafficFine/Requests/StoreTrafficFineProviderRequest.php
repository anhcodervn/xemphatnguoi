<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrafficFineProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(TrafficFineProviderSettingsService $providerSettings): array
    {
        $driver = (string) $this->input('driver');
        $driverConfiguration = (array) config("traffic-fines.sources.{$driver}", []);

        return [
            'label' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9][a-z0-9_-]*$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($providerSettings): void {
                    if ($providerSettings->exists((string) $value)) {
                        $fail('Mã provider đã tồn tại.');
                    }
                },
            ],
            'driver' => ['required', 'string', Rule::in($providerSettings->driverNames())],
            'enabled' => ['required', 'boolean'],
            'url' => [
                'required',
                'url',
                'starts_with:https://',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail) use ($driverConfiguration): void {
                    $allowedUrls = (array) ($driverConfiguration['allowed_urls'] ?? []);

                    if ($allowedUrls !== [] && ! in_array(trim((string) $value), $allowedUrls, true)) {
                        $fail('API URL không thuộc địa chỉ được phép của driver.');
                    }
                },
            ],
            'token' => ['required', 'string', 'max:2048'],
            'timeout' => ['required', 'integer', 'min:1', 'max:15'],
            'connect_timeout' => ['required', 'integer', 'min:1', 'max:5'],
            'retry_times' => ['required', 'integer', 'min:1', 'max:2'],
            'retry_sleep_ms' => ['required', 'integer', 'min:0', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Mã provider chỉ gồm chữ thường, số, dấu gạch ngang hoặc gạch dưới.',
            'driver.in' => 'Driver provider không được hỗ trợ.',
            'url.starts_with' => 'API URL phải sử dụng HTTPS.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422, [
            'errors' => $validator->errors(),
        ]);
    }
}
