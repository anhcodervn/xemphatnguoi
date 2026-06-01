<?php

use App\Features\Client\Recharge\Actions\StoreRechargeOrderAction;
use App\Models\RechargeMethod;
use App\Models\RechargeOrder;
use App\Models\User;
use App\Support\RechargeMethodCatalog;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('recharge_method_bank_account');
    Schema::dropIfExists('bank_accounts');
    Schema::dropIfExists('recharge_orders');
    Schema::dropIfExists('recharge_methods');
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

    Schema::create('recharge_methods', function ($table): void {
        $table->id();
        $table->string('code')->unique();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('badge_label')->nullable();
        $table->string('badge_type')->default('manual');
        $table->string('bank_name')->nullable();
        $table->string('account_number')->nullable();
        $table->string('account_name')->nullable();
        $table->decimal('min_amount', 16, 2)->default(0);
        $table->decimal('max_amount', 16, 2)->default(0);
        $table->unsignedInteger('bonus_percentage')->default(0);
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->json('metadata')->nullable();
        $table->timestamps();
    });

    Schema::create('bank_accounts', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('bank_name');
        $table->string('account_name');
        $table->string('account_number');
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('recharge_method_bank_account', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('recharge_method_id');
        $table->unsignedBigInteger('bank_account_id');
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('recharge_orders', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('recharge_method_id')->nullable();
        $table->unsignedBigInteger('bank_account_id')->nullable();
        $table->string('order_code')->unique();
        $table->string('method');
        $table->string('method_label');
        $table->decimal('amount', 16, 2)->default(0);
        $table->decimal('bonus_amount', 16, 2)->default(0);
        $table->decimal('total_amount', 16, 2)->default(0);
        $table->string('bank_name')->nullable();
        $table->string('account_number')->nullable();
        $table->string('account_name')->nullable();
        $table->string('transfer_content')->nullable();
        $table->string('status')->default(RechargeOrder::STATUS_PENDING);
        $table->timestamp('requested_at')->nullable();
        $table->timestamp('paid_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
    });
});

function createRechargeUser(): User
{
    return User::query()->create([
        'username' => 'recharge-user',
        'email' => 'recharge@example.com',
        'password' => 'password',
    ]);
}

test('store recharge order action builds default vietqr url for config methods', function () {
    $user = createRechargeUser();
    $action = new StoreRechargeOrderAction(new RechargeMethodCatalog);

    $order = $action->handle($user, [
        'method' => 'banking',
        'amount' => 500000,
    ]);

    expect($order->transfer_content)->toBe($order->order_code)
        ->and(data_get($order->metadata, 'qr_url'))->toBe(
            sprintf(
                'https://img.vietqr.io/image/BANKING-1029384756-compact.png?addInfo=%s&amount=500000',
                urlencode($order->order_code),
            ),
        );
});

test('store recharge order action uses custom qr template from recharge method metadata', function () {
    $user = createRechargeUser();

    RechargeMethod::query()->create([
        'code' => 'mb-bank',
        'name' => 'MB Bank',
        'description' => 'Nạp tự động',
        'badge_label' => 'Khuyến nghị',
        'badge_type' => 'auto',
        'bank_name' => 'MB',
        'account_number' => '88079999999',
        'account_name' => 'NGUYEN TUAN ANH',
        'min_amount' => 10000,
        'max_amount' => 100000000,
        'bonus_percentage' => 0,
        'sort_order' => 1,
        'is_active' => true,
        'metadata' => [
            'qr_template' => 'https://example.test/qr/{METHOD_CODE}/{ACCOUNT_NUMBER}?content={TRANSFER_CONTENT}&money={AMOUNT}',
        ],
    ]);

    $action = new StoreRechargeOrderAction(new RechargeMethodCatalog);

    $order = $action->handle($user, [
        'method' => 'mb-bank',
        'amount' => 150000,
    ]);

    expect(data_get($order->metadata, 'qr_url'))->toBe(
        sprintf(
            'https://example.test/qr/mb-bank/88079999999?content=%s&money=150000',
            urlencode($order->order_code),
        ),
    );
});
