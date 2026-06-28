<?php

namespace App\Features\Client\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionAutoRenewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'auto_renew_enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
