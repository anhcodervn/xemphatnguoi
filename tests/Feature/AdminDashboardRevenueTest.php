<?php

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Laravel\Sanctum\Sanctum;

test('admin dashboard separates package revenue and credited deposits by period', function (): void {
    $this->travelTo('2026-09-16 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $wallet = $admin->wallet()->firstOrFail();
    $referenceId = 1;

    $createWalletTransaction = function (
        string $type,
        string $referenceType,
        int $amount,
        string $status,
        string $createdAt,
    ) use ($wallet, &$referenceId): void {
        WalletTransaction::withoutEvents(function () use ($wallet, $type, $referenceType, $amount, $status, $createdAt, &$referenceId): void {
            $transaction = new WalletTransaction([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => 2_000_000,
                'balance_after' => $type === 'credit' ? 2_000_000 + $amount : 2_000_000 - $amount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId++,
                'description' => 'Dashboard revenue test',
                'status' => $status,
            ]);
            $transaction->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
        });
    };

    $createWalletTransaction('debit', 'monitoring_subscription', 100_000, 'success', '2026-09-16 09:00:00');
    $createWalletTransaction('debit', 'monitoring_subscription_upgrade', 200_000, 'success', '2026-09-14 09:00:00');
    $createWalletTransaction('debit', 'monitoring_subscription_renewal', 300_000, 'success', '2026-09-02 09:00:00');
    $createWalletTransaction('debit', 'traffic_fine_api_request', 400_000, 'success', '2026-09-16 10:00:00');
    $createWalletTransaction('debit', 'monitoring_subscription', 500_000, 'success', '2026-08-31 09:00:00');

    $createWalletTransaction('credit', PaymentTransaction::class, 150_000, 'success', '2026-09-16 08:00:00');
    $createWalletTransaction('credit', PaymentTransaction::class, 250_000, 'success', '2026-09-14 08:00:00');
    $createWalletTransaction('credit', PaymentTransaction::class, 350_000, 'success', '2026-09-03 08:00:00');
    $createWalletTransaction('credit', PaymentTransaction::class, 450_000, 'success', '2026-08-31 08:00:00');
    $createWalletTransaction('credit', PaymentTransaction::class, 900_000, 'failed', '2026-09-16 08:00:00');
    $createWalletTransaction('credit', User::class, 500_000, 'success', '2026-09-16 08:00:00');

    Sanctum::actingAs($admin);

    $this->getJson('/api/admin-api/traffic-fines/overview')
        ->assertOk()
        ->assertJsonPath('data.metrics.package_revenue.today', '100000')
        ->assertJsonPath('data.metrics.package_revenue.week', '300000')
        ->assertJsonPath('data.metrics.package_revenue.month', '600000')
        ->assertJsonPath('data.metrics.package_revenue.total', '1100000')
        ->assertJsonPath('data.metrics.wallet_deposits.today', '150000')
        ->assertJsonPath('data.metrics.wallet_deposits.week', '400000')
        ->assertJsonPath('data.metrics.wallet_deposits.month', '750000');
});

test('regular users cannot read admin revenue statistics', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/admin-api/traffic-fines/overview')->assertForbidden();
});
