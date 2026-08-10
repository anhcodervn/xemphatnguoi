<?php

namespace App\Features\Admin\Proxy\Services;

use App\Exceptions\ApiException;
use App\Features\Admin\Proxy\Resources\ProxyCategoryResource;
use App\Features\Admin\Proxy\Resources\ProxyProductResource;
use App\Features\Admin\Proxy\Resources\ProxyProviderResource;
use App\Models\ProxyCategory;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use Illuminate\Http\Request;

class ProxyCatalogService
{
    public function categoryList(Request $request): array
    {
        $categories = ProxyCategory::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 50), 1), 100));

        return ['categories' => [
            ...$categories->toArray(),
            'data' => ProxyCategoryResource::collection($categories->getCollection())->resolve(),
        ]];
    }

    public function providerList(Request $request): array
    {
        $providers = ProxyProvider::query()
            ->withCount('products')
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100));

        return ['providers' => [
            ...$providers->toArray(),
            'data' => ProxyProviderResource::collection($providers->getCollection())->resolve(),
        ]];
    }

    public function productList(Request $request): array
    {
        $products = ProxyProduct::query()
            ->with(['provider', 'category'])
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100));

        return [
            'products' => [
                ...$products->toArray(),
                'data' => ProxyProductResource::collection($products->getCollection())->resolve(),
            ],
            'providers' => ProxyProviderResource::collection(
                ProxyProvider::query()->where('is_active', true)->orderBy('priority')->get()
            )->resolve(),
            'categories' => ProxyCategoryResource::collection(
                ProxyCategory::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
            )->resolve(),
        ];
    }

    public function storeCategory(array $payload): ProxyCategory
    {
        return ProxyCategory::query()->create($payload);
    }

    public function updateCategory(ProxyCategory $category, array $payload): ProxyCategory
    {
        $category->update($payload);

        return $category->fresh();
    }

    public function deleteCategory(ProxyCategory $category): void
    {
        if ($category->products()->exists()) {
            throw new ApiException('Không thể xóa chuyên mục đang có sản phẩm proxy.', 422);
        }

        $category->delete();
    }

    public function storeProvider(array $payload): ProxyProvider
    {
        return ProxyProvider::query()->create($payload);
    }

    public function updateProvider(ProxyProvider $provider, array $payload): ProxyProvider
    {
        $provider->update($payload);

        return $provider->fresh();
    }

    public function deleteProvider(ProxyProvider $provider): void
    {
        $provider->delete();
    }

    public function storeProduct(array $payload): ProxyProduct
    {
        $product = ProxyProduct::query()->create($this->withDefaultProtocol($payload));

        return $product->load(['provider', 'category']);
    }

    public function updateProduct(ProxyProduct $product, array $payload): ProxyProduct
    {
        $product->update($this->withDefaultProtocol($payload));

        return $product->fresh(['provider', 'category']);
    }

    private function withDefaultProtocol(array $payload): array
    {
        if (isset($payload['supported_protocols']) && is_array($payload['supported_protocols']) && $payload['supported_protocols'] !== []) {
            $payload['protocol'] = $payload['supported_protocols'][0];
        }

        return $payload;
    }
}
