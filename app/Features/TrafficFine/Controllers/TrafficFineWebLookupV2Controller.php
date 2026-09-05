<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Exceptions\UnsupportedVehicleTypeException;
use App\Features\TrafficFine\Requests\TrafficFineLookupRequest;
use App\Features\TrafficFine\Services\ApiLookupBillingService;
use App\Features\TrafficFine\Services\CloudflareTurnstileService;
use App\Features\TrafficFine\Services\TrafficFineV2LookupService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TrafficFineWebLookupV2Controller extends Controller
{
    public function __invoke(
        TrafficFineLookupRequest $request,
        TrafficFineV2LookupService $lookupService,
        ApiLookupBillingService $billingService,
        CloudflareTurnstileService $turnstile,
    ): JsonResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        try {
            $turnstileResponse = $this->verifyTurnstile($turnstile, $request);

            if ($turnstileResponse instanceof JsonResponse) {
                return $turnstileResponse;
            }

            $billingService->ensureSufficientBalance(
                $request,
                $user,
                $billingService->v2PricePerRequest(),
            );

            $result = $lookupService->lookup(
                plate: $request->string('plate')->toString(),
                vehicleType: $request->string('vehicle_type')->toString(),
                user: $user,
                ip: $request->ip(),
            );

            $payload = $result->toArray();
            $transaction = $billingService->chargeWebV2($request, $user);
            $payload['billing'] = [
                'charged_amount' => (string) $transaction->amount,
                'balance' => (string) $transaction->balance_after,
            ];

            return response()
                ->json($payload)
                ->header('Cache-Control', 'private, no-store');
        } catch (UnsupportedVehicleTypeException $exception) {
            return response()->json([
                'success' => false,
                'status' => 'invalid_vehicle_type',
                'message' => $exception->getMessage(),
            ], 422);
        } catch (TrafficFineProviderException $exception) {
            return response()->json([
                'success' => false,
                'status' => 'provider_error',
                'message' => $exception->getMessage(),
            ], 503);
        }
    }

    private function verifyTurnstile(
        CloudflareTurnstileService $turnstile,
        TrafficFineLookupRequest $request,
    ): ?JsonResponse {
        return match ($turnstile->verifyPublicLookup($request)) {
            CloudflareTurnstileService::STATUS_REQUIRED => response()->json([
                'success' => false,
                'status' => 'captcha_required',
                'message' => 'Vui lòng hoàn tất xác minh bảo mật.',
            ], 422),
            CloudflareTurnstileService::STATUS_FAILED => response()->json([
                'success' => false,
                'status' => 'captcha_failed',
                'message' => 'Xác minh bảo mật không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.',
            ], 422),
            CloudflareTurnstileService::STATUS_UNAVAILABLE => response()->json([
                'success' => false,
                'status' => 'captcha_unavailable',
                'message' => 'Hệ thống xác minh đang tạm thời gián đoạn. Vui lòng thử lại sau.',
            ], 503),
            default => null,
        };
    }
}
