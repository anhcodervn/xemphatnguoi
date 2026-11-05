<?php

namespace App\Http\Middleware;

use App\Features\TrafficFine\Services\ApiLookupAvailabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrafficFineApiIsAvailable
{
    public function __construct(private readonly ApiLookupAvailabilityService $availability) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $apiVersion): Response
    {
        if (! $this->availability->isEnabled($apiVersion)) {
            return response()->json([
                'success' => false,
                'status' => 'api_maintenance',
                'message' => "Cổng tra cứu API {$apiVersion} đang bảo trì. Vui lòng thử lại sau.",
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
