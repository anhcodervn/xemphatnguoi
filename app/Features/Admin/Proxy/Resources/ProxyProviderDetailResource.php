<?php

namespace App\Features\Admin\Proxy\Resources;

use Illuminate\Http\Request;

class ProxyProviderDetailResource extends ProxyProviderResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'credentials' => $this->credentials,
        ];
    }
}
