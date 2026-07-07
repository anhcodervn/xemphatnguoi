<?php

namespace App\Console\Commands;

use App\Models\PackageOrder;
use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PaymentStatus;
use Illuminate\Console\Command;

class PrunePendingPackageOrdersCommand extends Command
{
    protected $signature = 'package:prune-pending-orders';

    protected $description = 'Xoa don goi captcha chua thanh toan sau thoi gian cau hinh.';

    public function handle(): int
    {
        $ttlHours = max(1, (int) config('services.captcha.pending_order_ttl_hours', 24));
        $cutoff = now()->subHours($ttlHours);

        $deleted = PackageOrder::query()
            ->where('payment_status', PaymentStatus::Pending)
            ->where('status', PackageOrderStatus::Pending)
            ->where('created_at', '<=', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Da xoa %d don goi chua thanh toan qua %d gio.',
            $deleted,
            $ttlHours,
        ));

        return self::SUCCESS;
    }
}
