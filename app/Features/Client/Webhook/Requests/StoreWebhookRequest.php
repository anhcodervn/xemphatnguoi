<?php

namespace App\Features\Client\Webhook\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->string('name')->trim()->value(),
            'url' => $this->string('url')->trim()->value(),
            'event_keyword' => $this->string('event_keyword')->trim()->lower()->value(),
            'status' => $this->string('status')->trim()->lower()->value(),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'event_keyword' => ['required', 'string', 'min:2', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên webhook',
            'url' => 'URL webhook',
            'event_keyword' => 'từ khóa event',
            'status' => 'trạng thái',
        ];
    }
}
