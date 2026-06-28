<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Utils\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKeyValue = trim((string) $request->header('X-API-KEY'));
        $apiSecret = trim((string) $request->header('X-API-SECRET'));

        if ($apiKeyValue === '' || $apiSecret === '') {
            return response()->json(ApiResponse::error('API key authentication is required.'), 401);
        }

        $apiKey = ApiKey::query()
            ->with('user')
            ->where('api_key', $apiKeyValue)
            ->first();

        if (! $apiKey instanceof ApiKey) {
            return response()->json(ApiResponse::error('API key authentication is required.'), 401);
        }

        $apiKey->markExpiredIfNeeded();

        if (! $apiKey->isActive() || ! $apiKey->matchesSecret($apiSecret) || ! $apiKey->allowsIp($request->ip())) {
            return response()->json(ApiResponse::error('API key authentication is required.'), 401);
        }

        $request->attributes->set('apiKey', $apiKey);
        $request->setUserResolver(static fn () => $apiKey->user);
        Auth::setUser($apiKey->user);

        return $next($request);
    }
}
