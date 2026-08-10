<?php

namespace App\Features\Admin\Proxy\Controllers;

use App\Features\Admin\Proxy\Requests\StoreProxyProviderRequest;
use App\Features\Admin\Proxy\Requests\UpdateProxyProviderRequest;
use App\Features\Admin\Proxy\Resources\ProxyProviderDetailResource;
use App\Features\Admin\Proxy\Resources\ProxyProviderResource;
use App\Features\Admin\Proxy\Services\ProxyCatalogService;
use App\Http\Controllers\Controller;
use App\Models\ProxyProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProxyProviderController extends Controller
{
    public function __construct(private readonly ProxyCatalogService $catalog) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $this->catalog->providerList($request)]);
    }

    public function show(ProxyProvider $proxyProvider): JsonResponse
    {
        return response()->json(['status' => true, 'data' => [
            'provider' => ProxyProviderDetailResource::make($proxyProvider)->resolve(),
        ]])->header('Cache-Control', 'no-store, private');
    }

    public function store(StoreProxyProviderRequest $request): JsonResponse
    {
        $provider = $this->catalog->storeProvider($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tạo nhà cung cấp proxy thành công.',
            'data' => ['provider' => ProxyProviderResource::make($provider)->resolve()],
        ], 201);
    }

    public function update(UpdateProxyProviderRequest $request, ProxyProvider $proxyProvider): JsonResponse
    {
        $provider = $this->catalog->updateProvider($proxyProvider, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật nhà cung cấp proxy thành công.',
            'data' => ['provider' => ProxyProviderResource::make($provider)->resolve()],
        ]);
    }

    public function destroy(ProxyProvider $proxyProvider): JsonResponse
    {
        $this->catalog->deleteProvider($proxyProvider);

        return response()->json(['status' => true, 'message' => 'Xóa nhà cung cấp proxy thành công.']);
    }
}
