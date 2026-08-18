<?php

namespace App\Features\N8nContent\Requests;

class UpdateN8nArticleRequest extends StoreN8nArticleRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(function (array $rules): array {
                return collect($rules)
                    ->reject(fn (mixed $rule): bool => $rule === 'required')
                    ->prepend('sometimes')
                    ->values()
                    ->all();
            })
            ->all();
    }
}
