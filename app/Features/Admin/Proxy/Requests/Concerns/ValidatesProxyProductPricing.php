<?php

namespace App\Features\Admin\Proxy\Requests\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesProxyProductPricing
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            $product = $this->route('proxyProduct');
            $basePrice = (float) $this->input('base_price', $product?->base_price ?? 0);
            $sellingPrice = (float) $this->input('selling_price', $product?->selling_price ?? 0);

            if ($sellingPrice < $basePrice) {
                $validator->errors()->add('selling_price', 'Giá bán mỗi ngày không được thấp hơn giá nhập mỗi ngày.');
            }
        }];
    }
}
