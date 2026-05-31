<?php

namespace App\Http\Middleware;

use App\Features\Client\Package\Services\PackageService;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasActiveSubscription
{
    public function __construct(
        private readonly PackageService $packageService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if ($this->packageService->getActiveSubscription($user) === null) {
            return $this->forbiddenResponse($request);
        }

        return $next($request);
    }

    private function forbiddenResponse(Request $request): Response
    {
        $message = 'Vui lòng đăng ký gói để truy cập vào trang này.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return new JsonResponse([
                'status' => false,
                'message' => $message,
            ], 403);
        }

        return redirect()->route('client.package');
    }
}
