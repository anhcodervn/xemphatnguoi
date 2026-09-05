<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrafficFineProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(TrafficFineProviderSettingsService $providerSettings): array
    {
        $provider = (string) $this->route('provider');
        $configuration = $providerSettings->configuration($provider);
        $requiresUrl = $this->boolean('enabled') && blank($configuration['url'] ?? null);
        $requiresToken = $this->boolean('enabled') && blank($configuration['token'] ?? null);

        return [
            'label' => ['sometimes', 'required', 'string', 'max:120'],
            'enabled' => ['required', 'boolean'],
            'url' => [
                Rule::requiredIf($requiresUrl),
                'nullable',
                'url',
                'starts_with:https://',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail) use ($configuration): void {
                    $allowedUrls = (array) ($configuration['allowed_urls'] ?? []);

                    if (filled($value) && $allowedUrls !== [] && ! in_array(trim((string) $value), $allowedUrls, true)) {
                        $fail('API URL không thuộc địa chỉ được phép của provider.');
                    }
                },
            ],
            'token' => [Rule::requiredIf($requiresToken), 'nullable', 'string', 'max:2048'],
            'timeout' => ['required', 'integer', 'min:1', 'max:15'],
            'connect_timeout' => ['required', 'integer', 'min:1', 'max:5'],
            'retry_times' => ['required', 'integer', 'min:1', 'max:2'],
            'retry_sleep_ms' => ['required', 'integer', 'min:0', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'Vui lòng nhập API URL trước khi bật provider.',
            'url.starts_with' => 'API URL phải sử dụng HTTPS.',
            'token.required' => 'Vui lòng nhập API token trước khi bật provider.',
        ];
    }

    public function attributes(): array
    {
        return [
            'enabled' => 'trạng thái provider',
            'url' => 'API URL',
            'token' => 'API token',
            'timeout' => 'thời gian chờ',
            'connect_timeout' => 'thời gian chờ kết nối',
            'retry_times' => 'số lần thử lại',
            'retry_sleep_ms' => 'thời gian nghỉ giữa các lần thử',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422, [
            'errors' => $validator->errors(),
        ]);
    }
}
