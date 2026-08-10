<?php

use App\Features\Client\Wallet\Services\WalletService;
use App\Jobs\SendSystemMailJob;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('wallet_transactions');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function ($table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('full_name')->nullable();
        $table->string('avatar')->nullable();
        $table->string('google_id')->nullable()->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamp('last_login_at')->nullable();
        $table->string('last_login_ip', 45)->nullable();
        $table->string('referral_code')->nullable()->unique();
        $table->unsignedBigInteger('referred_by')->nullable();
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('wallets', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('wallet_transactions', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('wallet_id');
        $table->string('type');
        $table->decimal('amount', 16, 2);
        $table->decimal('balance_before', 16, 2);
        $table->decimal('balance_after', 16, 2);
        $table->string('reference_type')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();
        $table->text('description')->nullable();
        $table->string('status')->default('success');
        $table->timestamps();
    });
});

function createWalletUser(string $email = 'wallet@example.com'): User
{
    return User::query()->create([
        'username' => 'wallet-user-'.str_replace(['@', '.'], '-', $email),
        'email' => $email,
        'password' => 'password',
    ]);
}

test('creating a successful credit wallet transaction queues a balance change mail', function () {
    Queue::fake();

    $user = createWalletUser('credit@example.com');
    $wallet = $user->wallet()->firstOrFail();

    WalletTransaction::query()->create([
        'wallet_id' => $wallet->id,
        'type' => 'credit',
        'amount' => 150000,
        'balance_before' => 100000,
        'balance_after' => 250000,
        'reference_type' => 'manual',
        'reference_id' => 1,
        'description' => 'Nạp tiền thành công',
        'status' => 'success',
    ]);

    Queue::assertPushed(SendSystemMailJob::class, function (SendSystemMailJob $job) use ($user): bool {
        return $job->to === $user->email
            && $job->subjectText === 'Hệ thống Auto Cron'
            && $job->title === 'Biến động tăng số dư ví'
            && in_array('Số tiền biến động: 150.000đ', $job->messageLines, true)
            && in_array('Số dư sau biến động: 250.000đ', $job->messageLines, true);
    });
});

test('debiting wallet through wallet service queues a balance change mail', function () {
    Queue::fake();

    $user = createWalletUser('debit@example.com');
    $wallet = $user->wallet()->firstOrFail();

    $wallet->forceFill([
        'balance' => 500000,
    ])->save();

    app(WalletService::class)->debit(
        user: $user,
        amount: 120000,
        referenceType: 'proxy_order',
        referenceId: 22,
        description: 'Thanh toán đơn proxy',
    );

    Queue::assertPushed(SendSystemMailJob::class, function (SendSystemMailJob $job) use ($user): bool {
        return $job->to === $user->email
            && $job->subjectText === 'Hệ thống Auto Cron'
            && $job->title === 'Biến động giảm số dư ví'
            && in_array('Số tiền biến động: 120.000đ', $job->messageLines, true)
            && in_array('Số dư sau biến động: 380.000đ', $job->messageLines, true)
            && in_array('Nội dung: Thanh toán đơn proxy', $job->messageLines, true);
    });
});
