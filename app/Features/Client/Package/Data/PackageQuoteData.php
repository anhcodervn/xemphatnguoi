<?php

namespace App\Features\Client\Package\Data;

use App\Models\Package;
use App\Models\Coupon;
use App\Models\UserSubscription;
use Carbon\CarbonInterface;

class PackageQuoteData
{
    public function __construct(
        public readonly Package $package,
        public readonly ?UserSubscription $sourceSubscription,
        public readonly string $quoteType,
        public readonly float $price,
        public readonly float $discountAmount,
        public readonly float $creditAmount,
        public readonly float $finalAmount,
        public readonly CarbonInterface $expiresAt,
        public readonly ?Coupon $coupon = null,
    ) {}

    /**
     * @return array{
     *     quote_type:string,
     *     price:float,
     *     discount_amount:float,
     *     credit_amount:float,
     *     final_amount:float,
     *     expires_at:string,
     *     coupon:array{id:int,code:string,name:string,type:string,value:string,max_discount_amount:string|null}|null,
     *     package:array{id:int,name:string,slug:string,duration_days:int},
     *     source_subscription:array{id:int,package_id:int,package_name:string,package_price:string,starts_at:string,expires_at:string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'quote_type' => $this->quoteType,
            'price' => $this->price,
            'discount_amount' => $this->discountAmount,
            'credit_amount' => $this->creditAmount,
            'final_amount' => $this->finalAmount,
            'expires_at' => $this->expiresAt->toISOString(),
            'coupon' => $this->coupon === null
                ? null
                : [
                    'id' => $this->coupon->id,
                    'code' => $this->coupon->code,
                    'name' => $this->coupon->name,
                    'type' => $this->coupon->type,
                    'value' => (string) $this->coupon->value,
                    'max_discount_amount' => $this->coupon->max_discount_amount !== null
                        ? (string) $this->coupon->max_discount_amount
                        : null,
                ],
            'package' => [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'slug' => $this->package->slug,
                'duration_days' => $this->package->duration_days,
            ],
            'source_subscription' => $this->sourceSubscription === null
                ? null
                : [
                    'id' => $this->sourceSubscription->id,
                    'package_id' => $this->sourceSubscription->package_id,
                    'package_name' => $this->sourceSubscription->package_name,
                    'package_price' => (string) $this->sourceSubscription->package_price,
                    'starts_at' => $this->sourceSubscription->starts_at?->toISOString(),
                    'expires_at' => $this->sourceSubscription->expires_at?->toISOString(),
                ],
        ];
    }
}
