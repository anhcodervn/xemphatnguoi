<?php

namespace App\Features\Admin\Package\Controllers;

use App\Features\Admin\Package\Requests\StorePackageRequest;
use App\Features\Admin\Package\Requests\UpdatePackageRequest;
use App\Features\Captcha\Support\CaptchaPlanCatalog;
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
            'data' => [
                ...$package->toArray(),
                'package_limits' => CaptchaPlanCatalog::resolve(
                    is_array($package->package_limits) ? $package->package_limits : null,
                ),
            ],
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
        $packageLimits = CaptchaPlanCatalog::resolve(is_array($validated['package_limits'] ?? null) ? $validated['package_limits'] : null);
        $features = collect($validated['features'] ?? [])
            ->map(fn (mixed $feature): string => trim((string) $feature))
            ->filter()
            ->values()
            ->all();

        return [
            ...$validated,
            'slug' => trim((string) $validated['slug']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'features' => $features,
            'package_limits' => $packageLimits,
            'account_limit' => (int) ($packageLimits['max_api_keys'] ?? 0),
            'can_buy_extra_account' => false,
            'extra_account_price' => 0,
            'request_limit' => (int) ($packageLimits['monthly_captcha_quota'] ?? 0),
            'request_per_minute' => (int) ($packageLimits['requests_per_minute'] ?? 60),
            'concurrent_limit' => (int) ($packageLimits['max_concurrent_tasks'] ?? 1),
        ];
    }
}
