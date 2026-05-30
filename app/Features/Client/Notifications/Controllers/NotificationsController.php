<?php

namespace App\Features\Client\Notifications\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NotificationsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'feature' => 'Client\Notifications',
            'message' => 'Notifications feature index',
        ]);
    }
}