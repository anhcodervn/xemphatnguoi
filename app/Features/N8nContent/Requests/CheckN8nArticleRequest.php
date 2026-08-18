<?php

namespace App\Features\N8nContent\Requests;

use Illuminate\Validation\Validator;

class CheckN8nArticleRequest extends N8nFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:5000'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'content_hash' => ['nullable', 'string', 'size:64'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $fields = ['title', 'slug', 'source_url', 'external_id', 'content_hash'];

                if (! collect($fields)->contains(fn (string $field): bool => $this->filled($field))) {
                    $validator->errors()->add('article', 'At least one duplicate identifier is required.');
                }
            },
        ];
    }
}
