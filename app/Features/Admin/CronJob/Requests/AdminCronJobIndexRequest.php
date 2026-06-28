<?php

namespace App\Features\Admin\CronJob\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCronJobIndexRequest extends FormRequest
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
            'package' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
