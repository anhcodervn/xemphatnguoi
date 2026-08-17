<?php

namespace App\Features\Client\ApiKey\Requests;

use App\Rules\IpAddressOrWildcard;
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
            'ip_whitelist.*' => ['string', 'max:120', new IpAddressOrWildcard],
            'expired_at' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'tên API key',
            'permissions' => 'quyền API',
            'ip_whitelist' => 'danh sách IP cho phép',
            'expired_at' => 'thời gian hết hạn',
        ];
    }
}
