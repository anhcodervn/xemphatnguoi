<?php

namespace App\Features\Admin\Package\Controllers;

use App\Features\Admin\Package\Requests\StorePackageRequest;
use App\Features\Admin\Package\Requests\UpdatePackageRequest;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\Enums\PackageStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        $packages = Package::query()
            ->withCount('userSubscriptions')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($packageQuery) use ($search): void {
                    $packageQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, array_column(PackageStatus::cases(), 'value'), true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => true,
            'data' => [
                'packages' => $packages,
                'summary' => [
                    'total' => Package::query()->count(),
                    'active' => Package::query()->where('status', PackageStatus::Active)->count(),
                    'inactive' => Package::query()->where('status', PackageStatus::Inactive)->count(),
                ],
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                ],
            ],
        ]);
    }

    public function show(Package $package): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $package,
        ]);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::query()->create($this->payload($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Tạo gói thành công.',
            'data' => $package,
        ], 201);
    }

    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $package->update($this->payload($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật gói thành công.',
            'data' => $package->fresh(),
        ]);
    }

    public function destroy(Package $package): JsonResponse
    {
        $package->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa gói thành công.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        $features = collect($validated['features'] ?? [])
            ->map(fn (mixed $feature): string => trim((string) $feature))
            ->filter()
            ->values()
            ->all();

        $canBuyExtraAccount = (bool) ($validated['can_buy_extra_account'] ?? false);

        return [
            ...$validated,
            'slug' => trim((string) $validated['slug']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'features' => $features,
            'can_buy_extra_account' => $canBuyExtraAccount,
            'extra_account_price' => $canBuyExtraAccount ? $validated['extra_account_price'] : 0,
        ];
    }
}
