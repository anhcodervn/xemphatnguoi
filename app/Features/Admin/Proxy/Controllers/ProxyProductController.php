<?php

namespace App\Features\Admin\Proxy\Controllers;

use App\Features\Admin\Proxy\Requests\StoreProxyProductRequest;
use App\Features\Admin\Proxy\Requests\UpdateProxyProductRequest;
use App\Features\Admin\Proxy\Resources\ProxyProductResource;
use App\Features\Admin\Proxy\Services\ProxyCatalogService;
use App\Http\Controllers\Controller;
use App\Models\ProxyProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProxyProductController extends Controller
{
    public function __construct(private readonly ProxyCatalogService $catalog) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $this->catalog->productList($request)]);
    }

    public function show(ProxyProduct $proxyProduct): JsonResponse
    {
        return response()->json(['status' => true, 'data' => [
            'product' => ProxyProductResource::make($proxyProduct->load(['provider', 'category']))->resolve(),
        ]]);
    }

    public function store(StoreProxyProductRequest $request): JsonResponse
    {
        $product = $this->catalog->storeProduct($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tạo sản phẩm proxy thành công.',
            'data' => ['product' => ProxyProductResource::make($product)->resolve()],
        ], 201);
    }

    public function update(UpdateProxyProductRequest $request, ProxyProduct $proxyProduct): JsonResponse
    {
        $product = $this->catalog->updateProduct($proxyProduct, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật sản phẩm proxy thành công.',
            'data' => ['product' => ProxyProductResource::make($product)->resolve()],
        ]);
    }
}
