<?php

namespace App\Features\Client\Proxy\Services;

use App\Features\Client\Proxy\Resources\ProxyCategoryResource;
use App\Features\Client\Proxy\Resources\ProxyProductResource;
use App\Models\ProxyCategory;
use App\Models\ProxyProduct;

class ProxyCatalogService
{
    public function products(): array
    {
        return ProxyProductResource::collection(
            ProxyProduct::query()
                ->where('is_active', true)
                ->whereHas('provider', fn ($query) => $query->where('is_active', true))
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->with('category')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        )->resolve();
    }

    public function categories(): array
    {
        $categories = ProxyCategory::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('provider', fn ($providerQuery) => $providerQuery->where('is_active', true)))
            ->with(['products' => fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('provider', fn ($providerQuery) => $providerQuery->where('is_active', true))
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return ProxyCategoryResource::collection($categories)->resolve();
    }
}
