<?php

namespace App\Service\Reporting;

use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Utils\SendMessage;

class ProxySalesReporter
{
    public function reportFulfilled(ProxyOrder $proxyOrder): void
    {
        $order = ProxyOrder::query()
            ->with(['user:id,username,full_name,email', 'product:id,name,code,base_price', 'provider:id,name,code'])
            ->find($proxyOrder->id);

        if (! $order instanceof ProxyOrder || $order->status !== ProxyOrder::STATUS_FULFILLED) {
            return;
        }

        $walletTransaction = WalletTransaction::query()
            ->where('reference_type', ProxyOrder::class)
            ->where('reference_id', $order->id)
            ->where('type', 'debit')
            ->where('status', 'success')
            ->latest('id')
            ->first();

        $revenue = (float) $order->total_amount;
        $cost = $this->cost($order, $order->product);
        $user = $order->user;
        $provider = $order->provider;

        SendMessage::sendSalesReport($this->operationTitle($order->type), [
            'Mã đơn' => $order->order_code,
            'Thao tác' => $this->operationLabel($order->type),
            'User ID' => $order->user_id,
            'Người dùng' => $this->userLabel($user),
            'Sản phẩm' => $order->product_name,
            'Số lượng' => $order->quantity,
            'Số ngày' => $order->duration_days,
            'Nhà cung cấp' => $this->providerLabel($provider),
            'Doanh thu' => $this->money($revenue),
            'Giá vốn' => $cost === null ? 'Chưa cấu hình' : $this->money($cost),
            'Lợi nhuận tạm tính' => $cost === null ? 'Chưa xác định' : $this->money($revenue - $cost),
            'Số dư trước' => $walletTransaction?->balance_before !== null
                ? $this->money((float) $walletTransaction->balance_before)
                : 'Không xác định',
            'Số dư sau' => $walletTransaction?->balance_after !== null
                ? $this->money((float) $walletTransaction->balance_after)
                : 'Không xác định',
            'Hoàn tất lúc' => $order->fulfilled_at,
        ]);
    }

    private function cost(ProxyOrder $order, ?ProxyProduct $product): ?float
    {
        if ($order->type === ProxyOrder::TYPE_CHANGE || ! $product instanceof ProxyProduct) {
            return null;
        }

        $basePrice = (float) $product->base_price;

        if ($basePrice <= 0) {
            return null;
        }

        return round($basePrice * $order->quantity * $order->duration_days, 2);
    }

    private function operationTitle(string $type): string
    {
        return match ($type) {
            ProxyOrder::TYPE_RENEW => 'Gia hạn proxy thành công',
            ProxyOrder::TYPE_CHANGE => 'Đổi proxy thành công',
            default => 'Bán proxy thành công',
        };
    }

    private function operationLabel(string $type): string
    {
        return match ($type) {
            ProxyOrder::TYPE_RENEW => 'Gia hạn',
            ProxyOrder::TYPE_CHANGE => 'Đổi proxy',
            default => 'Mua mới',
        };
    }

    private function userLabel(?User $user): string
    {
        return (string) ($user?->full_name ?: $user?->username ?: $user?->email ?: '--');
    }

    private function providerLabel(?ProxyProvider $provider): string
    {
        if (! $provider instanceof ProxyProvider) {
            return '--';
        }

        return "{$provider->name} ({$provider->code})";
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.').' đ';
    }
}
