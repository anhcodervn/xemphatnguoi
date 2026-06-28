<?php

namespace App\Features\Client\CronJob\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CronJobLogIndexRequest extends FormRequest
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
            'status' => ['nullable', 'string', 'in:success,failed,timeout,error,blocked'],
            'status_code' => ['nullable', 'integer', 'min:100', 'max:599'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
