<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Exceptions\UnsupportedVehicleTypeException;
use App\Features\TrafficFine\Requests\TrafficFineLookupRequest;
use App\Features\TrafficFine\Services\ApiLookupBillingService;
use App\Features\TrafficFine\Services\CloudflareTurnstileService;
use App\Features\TrafficFine\Services\TrafficFineLookupService;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TrafficFineLookupController extends Controller
{
    public function __invoke(
        TrafficFineLookupRequest $request,
        TrafficFineLookupService $lookupService,
        ApiLookupBillingService $billingService,
        CloudflareTurnstileService $turnstile,
    ): JsonResponse {
        try {
            if ($request->routeIs('traffic-fines.lookup')) {
                $turnstileResponse = $this->verifyTurnstile($turnstile, $request);

                if ($turnstileResponse instanceof JsonResponse) {
                    return $turnstileResponse;
                }
            }

            $apiKey = $request->attributes->get('apiKey');

            if ($apiKey instanceof ApiKey) {
                /** @var User $user */
                $user = $apiKey->user;
                $billingService->ensureSufficientBalance($request, $user);
            }

            $result = $lookupService->lookup(
                plate: $request->string('plate')->toString(),
                vehicleType: $request->string('vehicle_type')->toString(),
                user: $request->user(),
                ip: $request->ip(),
            );

            $payload = $result->toArray();
            $request->attributes->set('service_response_data', $payload['data']);
            request()->attributes->set('service_response_data', $payload['data']);

            if ($apiKey instanceof ApiKey) {
                $billingService->charge($request, $user, $apiKey);
            }

            $response = response()->json($payload);

            if ($apiKey instanceof ApiKey) {
                $response->headers->set('Cache-Control', 'private, no-store');
            }

            return $response;
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
