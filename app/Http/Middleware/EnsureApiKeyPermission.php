<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Utils\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('apiKey');

        if (! $apiKey instanceof ApiKey) {
            return response()->json(ApiResponse::error('API key authentication is required.'), 401);
        }

        if ($permissions === []) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($apiKey->allowsPermission($permission)) {
                return $next($request);
            }
        }

        return response()->json(ApiResponse::error('This API key does not have permission to access the requested endpoint.'), 403);
    }
}
