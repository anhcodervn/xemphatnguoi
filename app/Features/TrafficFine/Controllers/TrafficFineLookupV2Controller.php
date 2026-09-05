<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Exceptions\UnsupportedVehicleTypeException;
use App\Features\TrafficFine\Requests\TrafficFineLookupRequest;
use App\Features\TrafficFine\Services\ApiLookupBillingService;
use App\Features\TrafficFine\Services\TrafficFineV2LookupService;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TrafficFineLookupV2Controller extends Controller
{
    public function __invoke(
        TrafficFineLookupRequest $request,
        TrafficFineV2LookupService $lookupService,
        ApiLookupBillingService $billingService,
    ): JsonResponse {
        try {
            $apiKey = $request->attributes->get('apiKey');

            if (! $apiKey instanceof ApiKey) {
                abort(401);
            }

            /** @var User $user */
            $user = $apiKey->user;
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
            $request->attributes->set('service_response_data', $payload['data']);
            request()->attributes->set('service_response_data', $payload['data']);
            $billingService->charge($request, $user, $apiKey);

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
}
