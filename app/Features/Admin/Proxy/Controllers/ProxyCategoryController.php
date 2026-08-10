<?php

namespace App\Features\Admin\Proxy\Controllers;

use App\Features\Admin\Proxy\Requests\StoreProxyCategoryRequest;
use App\Features\Admin\Proxy\Requests\UpdateProxyCategoryRequest;
use App\Features\Admin\Proxy\Resources\ProxyCategoryResource;
use App\Features\Admin\Proxy\Services\ProxyCatalogService;
use App\Http\Controllers\Controller;
use App\Models\ProxyCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProxyCategoryController extends Controller
{
    public function __construct(private readonly ProxyCatalogService $catalog) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $this->catalog->categoryList($request)]);
    }

    public function store(StoreProxyCategoryRequest $request): JsonResponse
    {
        $category = $this->catalog->storeCategory($request->validated());

        return response()->json(['status' => true, 'data' => ['category' => ProxyCategoryResource::make($category)->resolve()]], 201);
    }

    public function show(ProxyCategory $proxyCategory): JsonResponse
    {
        return response()->json(['status' => true, 'data' => ['category' => ProxyCategoryResource::make($proxyCategory)->resolve()]]);
    }

    public function update(UpdateProxyCategoryRequest $request, ProxyCategory $proxyCategory): JsonResponse
    {
        $category = $this->catalog->updateCategory($proxyCategory, $request->validated());

        return response()->json(['status' => true, 'data' => ['category' => ProxyCategoryResource::make($category)->resolve()]]);
    }

    public function destroy(ProxyCategory $proxyCategory): JsonResponse
    {
        $this->catalog->deleteCategory($proxyCategory);

        return response()->json(['status' => true, 'message' => 'Xóa chuyên mục proxy thành công.']);
    }
}
