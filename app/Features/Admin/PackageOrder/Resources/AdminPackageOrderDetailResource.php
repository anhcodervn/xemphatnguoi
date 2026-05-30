<?php

namespace App\Features\Admin\PackageOrder\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPackageOrderDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource['order'];
        $walletTransaction = $this->resource['wallet_transaction'];
        $paymentTransaction = $this->resource['payment_transaction'];

        return [
            'order' => AdminPackageOrderResource::make($order)->resolve(),
            'package_snapshot' => $order->package ? [
                'id' => $order->package->id,
                'name' => $order->package->name,
                'description' => $order->package->description,
                'duration_days' => $order->package->duration_days,
                'status' => $order->package->status,
            ] : null,
            'subscription' => $order->subscription ? [
                'id' => $order->subscription->id,
                'package_name' => $order->subscription->package_name,
                'starts_at' => $order->subscription->starts_at?->toISOString(),
                'expires_at' => $order->subscription->expires_at?->toISOString(),
                'status' => $order->subscription->status?->value ?? $order->subscription->status,
            ] : null,
            'wallet_transaction' => $walletTransaction ? [
                'id' => $walletTransaction->id,
                'amount' => (float) $walletTransaction->amount,
                'status' => $walletTransaction->status,
                'created_at' => $walletTransaction->created_at?->toISOString(),
            ] : null,
            'payment_transaction' => $paymentTransaction ? [
                'id' => $paymentTransaction->id,
                'transaction_code' => $paymentTransaction->transaction_code,
                'amount' => (float) $paymentTransaction->amount,
                'status' => $paymentTransaction->status,
                'created_at' => $paymentTransaction->created_at?->toISOString(),
            ] : null,
        ];
    }
}
