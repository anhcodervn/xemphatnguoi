<?php

namespace App\Features\Client\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkExtraAccountOrderPaidRequest extends FormRequest
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
        return [];
    }
}
