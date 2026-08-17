<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Requests\AdSlotRequest;
use App\Http\Controllers\Controller;
use App\Models\AdSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AdSlotController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'slots' => AdSlot::query()->orderBy('name')->get(),
            ],
        ]);
    }

    public function store(AdSlotRequest $request): JsonResponse
    {
        $slot = AdSlot::query()->create($request->validated());
        $this->forgetSlot($slot->name);

        return response()->json([
            'status' => true,
            'message' => 'Đã tạo vị trí quảng cáo.',
            'data' => $slot,
        ], 201);
    }

    public function show(AdSlot $adSlot): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $adSlot,
        ]);
    }

    public function update(AdSlotRequest $request, AdSlot $adSlot): JsonResponse
    {
        $oldName = $adSlot->name;
        $adSlot->update($request->validated());
        $this->forgetSlot($oldName);
        $this->forgetSlot($adSlot->name);

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật vị trí quảng cáo.',
            'data' => $adSlot->fresh(),
        ]);
    }

    public function destroy(AdSlot $adSlot): JsonResponse
    {
        $name = $adSlot->name;
        $adSlot->delete();
        $this->forgetSlot($name);

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa vị trí quảng cáo.',
        ]);
    }

    private function forgetSlot(string $name): void
    {
        Cache::forget("ad_slot:{$name}");
    }
}
