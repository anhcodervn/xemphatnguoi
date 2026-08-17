<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Exceptions\UnsupportedVehicleTypeException;
use App\Features\TrafficFine\Requests\UserVehicleRequest;
use App\Features\TrafficFine\Services\TrafficFineLookupService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserVehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewAny', UserVehicle::class);

        return response()->json([
            'status' => true,
            'data' => [
                'vehicles' => $user->vehicles()
                    ->with('monitoring:id,user_vehicle_id,enabled,last_checked_at,last_violation_count')
                    ->latest('id')
                    ->get(),
            ],
        ]);
    }

    public function store(UserVehicleRequest $request): JsonResponse
    {
        $this->authorize('create', UserVehicle::class);

        /** @var User $user */
        $user = $request->user();
        $vehicle = $user->vehicles()->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Đã thêm xe vào garage.',
            'data' => $vehicle,
        ], 201);
    }

    public function show(UserVehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        return response()->json([
            'status' => true,
            'data' => $vehicle->load('monitoring'),
        ]);
    }

    public function update(UserVehicleRequest $request, UserVehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);
        $vehicle->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật thông tin xe.',
            'data' => $vehicle->fresh()->load('monitoring'),
        ]);
    }

    public function destroy(UserVehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);
        $vehicle->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa xe khỏi garage.',
        ]);
    }

    public function lookup(
        Request $request,
        UserVehicle $vehicle,
        TrafficFineLookupService $lookupService,
    ): JsonResponse {
        $this->authorize('view', $vehicle);

        try {
            $result = $lookupService->lookup(
                plate: $vehicle->plate,
                vehicleType: $vehicle->vehicle_type,
                user: $request->user(),
                ip: $request->ip(),
            );
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

        return response()->json($result->toArray());
    }
}
