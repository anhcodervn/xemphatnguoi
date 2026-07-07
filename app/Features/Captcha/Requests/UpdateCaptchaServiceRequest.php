<?php

namespace App\Features\Captcha\Requests;

use Illuminate\Validation\Rule;

class UpdateCaptchaServiceRequest extends StoreCaptchaServiceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $captchaService = $this->route('captchaService');

        $rules['code'] = [
            'required',
            'string',
            'max:100',
            Rule::unique('captcha_services', 'code')->ignore($captchaService?->id),
        ];

        return $rules;
    }
}
