<?php

namespace App\Features\Client\ApiKey\Requests;

use App\Models\ApiKey;
use App\Rules\IpAddressOrWildcard;
use App\Support\ApiPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'permissions' => ['sometimes', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(ApiPermissionCatalog::keys())],
            'ip_whitelist' => ['sometimes', 'array'],
            'ip_whitelist.*' => ['string', 'max:120', new IpAddressOrWildcard],
            'expired_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in([
                ApiKey::STATUS_ACTIVE,
                ApiKey::STATUS_INACTIVE,
                ApiKey::STATUS_REVOKED,
            ])],
        ];
    }
}
