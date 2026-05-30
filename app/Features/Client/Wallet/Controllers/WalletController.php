<?php

namespace App\Features\Client\Wallet\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'feature' => 'Client\Wallet',
            'message' => 'Wallet feature index',
        ]);
    }
}