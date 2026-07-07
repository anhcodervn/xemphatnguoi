<?php

namespace App\Features\Captcha\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CaptchaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'feature' => 'Captcha',
            'message' => 'Captcha feature index',
        ]);
    }
}
