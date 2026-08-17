<?php

use App\Events\WalletBalanceChanged;
use App\Features\Admin\User\Actions\AdjustUserWalletAction;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\Notification;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Event;

it('broadcasts the committed wallet balance after a paid client operation', function () {
    $user = User::factory()->create();
    $wallet = $user->wallet()->firstOrFail();
    $wallet->forceFill([
        'balance' => 20000,
        'total_spent' => 0,
    ])->save();

    Event::fake([WalletBalanceChanged::class]);

    app(WalletService::class)->debit(
        user: $user,
        amount: 5000,
        referenceType: 'traffic_fine_package',
        referenceId: 99,
        description: 'Thanh toán gói tra cứu',
    );

    expect($wallet->refresh()->balance)->toBe('15000.00')
        ->and($wallet->total_spent)->toBe('5000.00');

    Event::assertDispatched(WalletBalanceChanged::class, fn (WalletBalanceChanged $event): bool => $event->userId === $user->id
        && $event->walletType === Wallet::TYPE_MAIN
        && $event->balance === '15000.00'
        && $event->totalSpent === '5000.00'
        && $event->changeType === 'debit'
        && $event->amount === '-5000.00'
        && $event->notification === null
        && $event->broadcastAs() === 'wallet.balance.changed');
});

it('notifies the user and broadcasts the balance when an admin adjusts the wallet', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $wallet = $user->wallet()->firstOrFail();
    $wallet->forceFill(['balance' => 1000])->save();

    Event::fake([WalletBalanceChanged::class]);

    app(AdjustUserWalletAction::class)->handle($user, [
        'type' => 'add',
        'amount' => 2500,
        'note' => 'Khuyến mãi',
    ], $admin);

    expect($wallet->refresh()->balance)->toBe('3500.00');

    $notification = Notification::query()->whereBelongsTo($user)->latest('id')->firstOrFail();

    expect($notification->title)->toBe('Tài khoản được cộng tiền')
        ->and($notification->content)->toContain('Admin đã cộng 2.500đ')
        ->and($notification->content)->toContain('Khuyến mãi')
        ->and($notification->redirect_url)->toBe('/wallet')
        ->and($notification->type)->toBe('success');

    Event::assertDispatched(WalletBalanceChanged::class, fn (WalletBalanceChanged $event): bool => $event->userId === $user->id
        && $event->balance === '3500.00'
        && $event->changeType === 'adjustment'
        && $event->amount === '2500.00'
        && $event->notification !== null
        && $event->notification['id'] === $notification->id);
});
