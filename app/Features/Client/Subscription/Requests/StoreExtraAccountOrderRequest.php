<?php

namespace App\Features\Client\Subscription\Requests;

use App\Models\UserSubscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExtraAccountOrderRequest extends FormRequest
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
            'user_subscription_id' => [
                'required',
                'integer',
                Rule::exists(UserSubscription::class, 'id'),
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
        ];
    }
}
