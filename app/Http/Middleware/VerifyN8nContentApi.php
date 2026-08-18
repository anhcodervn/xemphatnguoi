<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyN8nContentApi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.n8n_content.enabled', false)) {
            return response()->json([
                'success' => false,
                'message' => 'N8N content API is disabled.',
            ], 403);
        }

        $configuredKey = (string) config('services.n8n_content.key', '');
        $providedKey = trim((string) $request->header('X-N8N-API-KEY'));

        if ($configuredKey === '' || $providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid N8N API key.',
            ], 401);
        }

        return $next($request);
    }
}
