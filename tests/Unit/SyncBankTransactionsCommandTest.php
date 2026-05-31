<?php

use App\Features\Api\V1\Actions\MatchRechargeClientOrdersAction;
use App\Features\Client\Bank\Actions\TransactionBankAction;
use App\Features\Client\Package\Services\PackageService;
use App\Jobs\SyncBankAccountTransactionsJob;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Queue::fake();

    Schema::dropIfExists('bank_accounts');
    Schema::dropIfExists('banks');
    Schema::dropIfExists('user_subscriptions');
    Schema::dropIfExists('packages');
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

    Schema::create('packages', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->decimal('price', 16, 2)->default(0);
        $table->integer('duration_days')->default(30);
        $table->integer('account_limit')->default(1);
        $table->boolean('can_buy_extra_account')->default(false);
        $table->decimal('extra_account_price', 16, 2)->default(0);
        $table->integer('request_limit')->default(0);
        $table->integer('request_per_minute')->default(0);
        $table->integer('concurrent_limit')->default(0);
        $table->json('features')->nullable();
        $table->string('status')->default(PackageStatus::Active->value);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('user_subscriptions', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('package_id');
        $table->unsignedBigInteger('order_id')->nullable();
        $table->string('package_name');
        $table->decimal('package_price', 16, 2)->default(0);
        $table->integer('base_account_limit')->default(1);
        $table->integer('extra_account_limit')->default(0);
        $table->integer('used_account')->default(0);
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->string('status')->default(SubscriptionStatus::Active->value);
        $table->timestamps();
    });

    Schema::create('banks', function ($table): void {
        $table->id();
        $table->string('code')->unique();
        $table->string('name');
        $table->string('short_name')->nullable();
        $table->string('logo')->nullable();
        $table->string('bg_color')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->integer('limit_request_per_minute')->default(1);
        $table->json('metadata')->nullable();
        $table->timestamps();
    });

    Schema::create('bank_accounts', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('bank_name');
        $table->string('account_name');
        $table->string('account_number');
        $table->string('username')->nullable();
        $table->string('password')->nullable();
        $table->text('token')->nullable();
        $table->text('data_login')->nullable();
        $table->string('proxy')->nullable();
        $table->string('status')->default('active');
        $table->timestamp('last_sync_at')->nullable();
        $table->timestamps();
    });
});

function createSubscriptionPackage(): Package
{
    return Package::query()->create([
        'name' => 'Business',
        'slug' => 'business',
        'price' => 499000,
        'duration_days' => 30,
        'account_limit' => 3,
        'can_buy_extra_account' => false,
        'extra_account_price' => 0,
        'request_limit' => 10000,
        'request_per_minute' => 60,
        'concurrent_limit' => 5,
        'status' => PackageStatus::Active,
    ]);
}

function attachSubscription(User $user, Package $package, DateTimeInterface $expiresAt): UserSubscription
{
    return UserSubscription::query()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'base_account_limit' => 3,
        'extra_account_limit' => 0,
        'used_account' => 1,
        'starts_at' => now()->subDays(2),
        'expires_at' => $expiresAt,
        'status' => SubscriptionStatus::Active,
    ]);
}

test('bank sync command only dispatches jobs for accounts owned by users with active subscriptions', function () {
    $package = createSubscriptionPackage();

    $activeUser = User::query()->create([
        'username' => 'active-owner',
        'email' => 'active-owner@example.com',
        'password' => 'password',
    ]);

    $expiredUser = User::query()->create([
        'username' => 'expired-owner',
        'email' => 'expired-owner@example.com',
        'password' => 'password',
    ]);

    attachSubscription($activeUser, $package, now()->addDays(5));
    attachSubscription($expiredUser, $package, now()->subMinute());

    Bank::query()->create([
        'code' => 'acb',
        'name' => 'Asia Commercial Bank',
        'short_name' => 'ACB',
        'is_active' => true,
        'sort_order' => 1,
        'limit_request_per_minute' => 5,
    ]);

    $activeAccount = BankAccount::query()->create([
        'user_id' => $activeUser->id,
        'bank_name' => 'acb',
        'account_name' => 'Eligible Account',
        'account_number' => '123456789',
        'username' => 'eligible',
        'status' => 'active',
    ]);

    BankAccount::query()->create([
        'user_id' => $expiredUser->id,
        'bank_name' => 'acb',
        'account_name' => 'Expired Account',
        'account_number' => '987654321',
        'username' => 'expired',
        'status' => 'active',
    ]);

    $this->artisan('bank:sync-transactions', [
        '--bank' => 'acb',
        '--transaction-limit' => 20,
    ])->assertSuccessful();

    Queue::assertPushed(SyncBankAccountTransactionsJob::class, 1);
    Queue::assertPushed(SyncBankAccountTransactionsJob::class, function (SyncBankAccountTransactionsJob $job) use ($activeAccount): bool {
        return $job->bankAccountId === $activeAccount->id;
    });
});

test('sync bank job skips processing when owner subscription is no longer active', function () {
    $package = createSubscriptionPackage();

    $expiredUser = User::query()->create([
        'username' => 'expired-job-owner',
        'email' => 'expired-job-owner@example.com',
        'password' => 'password',
    ]);

    attachSubscription($expiredUser, $package, now()->subMinute());

    $bankAccount = BankAccount::query()->create([
        'user_id' => $expiredUser->id,
        'bank_name' => 'acb',
        'account_name' => 'Expired Job Account',
        'account_number' => '222333444',
        'username' => 'expired-job',
        'status' => 'active',
    ]);

    $transactionAction = Mockery::mock(TransactionBankAction::class);
    $transactionAction->shouldNotReceive('handleWithChanges');

    $matchRechargeClientOrdersAction = Mockery::mock(MatchRechargeClientOrdersAction::class);
    $matchRechargeClientOrdersAction->shouldNotReceive('handle');

    $job = new SyncBankAccountTransactionsJob($bankAccount->id, 20);
    $job->handle($transactionAction, $matchRechargeClientOrdersAction, app(PackageService::class));

    expect(true)->toBeTrue();
});
