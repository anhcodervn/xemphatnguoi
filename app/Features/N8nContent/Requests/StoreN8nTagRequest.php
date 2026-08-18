<?php

namespace App\Features\N8nContent\Requests;

class StoreN8nTagRequest extends N8nFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ];
    }
}
