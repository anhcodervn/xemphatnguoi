<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdSlotRequest extends FormRequest
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
        $slotId = $this->route('ad_slot')?->id ?? $this->route('ad_slot');

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('ad_slots', 'name')->ignore($slotId)],
            'code' => ['nullable', 'string'],
            'enabled' => ['sometimes', 'boolean'],
            'device' => ['required', Rule::in(['all', 'desktop', 'mobile'])],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Tên vị trí chỉ gồm chữ thường, số và dấu gạch dưới.',
            'end_at.after_or_equal' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên vị trí',
            'code' => 'mã quảng cáo',
            'enabled' => 'trạng thái',
            'device' => 'thiết bị',
            'start_at' => 'thời gian bắt đầu',
            'end_at' => 'thời gian kết thúc',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
