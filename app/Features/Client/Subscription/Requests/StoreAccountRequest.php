<?php

namespace App\Features\Client\Subscription\Requests;

use App\Models\UserSubscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
            'subscription_id' => [
                'required',
                'integer',
                Rule::exists(UserSubscription::class, 'id'),
            ],
        ];
    }
}
