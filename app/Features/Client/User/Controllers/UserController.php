<?php

namespace App\Features\Client\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'feature' => 'Client\User',
            'message' => 'User feature index',
        ]);
    }
}