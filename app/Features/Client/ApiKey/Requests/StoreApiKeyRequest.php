<?php

namespace App\Features\Client\ApiKey\Requests;

use App\Support\ApiPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(ApiPermissionCatalog::keys())],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['string', 'max:120'],
            'expired_at' => ['nullable', 'date'],
        ];
    }
}
