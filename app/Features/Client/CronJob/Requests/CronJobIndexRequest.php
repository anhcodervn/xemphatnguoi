<?php

namespace App\Features\Client\CronJob\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CronJobIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:active,paused,disabled'],
            'method' => ['nullable', 'string', 'in:GET,POST,PUT,PATCH,DELETE'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
