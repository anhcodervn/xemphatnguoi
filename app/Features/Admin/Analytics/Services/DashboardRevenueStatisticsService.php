<?php

namespace App\Features\Admin\Analytics\Services;

use App\Models\PaymentTransaction;
use App\Models\WalletTransaction;

class DashboardRevenueStatisticsService
{
    private const PACKAGE_REFERENCE_TYPES = [
        'monitoring_subscription',
        'monitoring_subscription_upgrade',
        'monitoring_subscription_renewal',
    ];

    /**
     * @return array{
     *     package_revenue: array{today: string, week: string, month: string, total: string},
     *     wallet_deposits: array{today: string, week: string, month: string}
     * }
     */
    public function summary(): array
    {
        $today = now()->startOfDay();
        $week = now()->startOfWeek();
        $month = now()->startOfMonth();

        $packageRevenue = WalletTransaction::query()
            ->where('type', 'debit')
            ->where('status', 'success')
            ->whereIn('reference_type', self::PACKAGE_REFERENCE_TYPES)
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END) as today', [$today])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END) as week', [$week])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END) as month', [$month])
            ->selectRaw('SUM(amount) as total')
            ->first();

        $walletDeposits = WalletTransaction::query()
            ->where('type', 'credit')
            ->where('status', 'success')
            ->where('reference_type', PaymentTransaction::class)
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END) as today', [$today])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END) as week', [$week])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END) as month', [$month])
            ->first();

        return [
            'package_revenue' => [
                'today' => (string) ($packageRevenue?->today ?? '0.00'),
                'week' => (string) ($packageRevenue?->week ?? '0.00'),
                'month' => (string) ($packageRevenue?->month ?? '0.00'),
                'total' => (string) ($packageRevenue?->total ?? '0.00'),
            ],
            'wallet_deposits' => [
                'today' => (string) ($walletDeposits?->today ?? '0.00'),
                'week' => (string) ($walletDeposits?->week ?? '0.00'),
                'month' => (string) ($walletDeposits?->month ?? '0.00'),
            ],
        ];
    }
}
