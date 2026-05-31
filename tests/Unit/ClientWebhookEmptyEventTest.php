<?php

use App\Exceptions\ApiException;
use App\Features\Client\Webhook\Actions\DispatchWebhookAction;
use App\Features\Client\Webhook\Actions\StoreWebhookAction;
use App\Features\Client\Webhook\Actions\UpdateWebhookAction;
use App\Jobs\DispatchWebhookJob;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Queue::fake();

    Schema::dropIfExists('webhooks');
    Schema::dropIfExists('bank_accounts');
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

    Schema::create('bank_accounts', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('bank_name');
        $table->string('account_name');
        $table->string('account_number');
        $table->text('username')->nullable();
        $table->text('password')->nullable();
        $table->text('token')->nullable();
        $table->text('data_login')->nullable();
        $table->text('proxy')->nullable();
        $table->string('status')->default('active');
        $table->timestamp('last_sync_at')->nullable();
        $table->timestamps();
    });

    Schema::create('webhooks', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('bank_account_id');
        $table->string('name')->nullable();
        $table->string('url');
        $table->string('secret_key');
        $table->string('event_keyword')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });
});

function createWebhookOwnerAndBank(): array
{
    $user = User::query()->create([
        'username' => 'webhook-owner-'.Str::lower(Str::random(4)),
        'email' => Str::lower(Str::random(8)).'@example.com',
        'password' => 'password',
    ]);

    $bankAccount = BankAccount::query()->create([
        'user_id' => $user->id,
        'bank_name' => 'acb',
        'account_name' => 'Webhook Owner',
        'account_number' => '123456789',
        'status' => 'active',
    ]);

    return [$user, $bankAccount];
}

test('store webhook action keeps empty event keyword as dispatch all marker', function () {
    [$user, $bankAccount] = createWebhookOwnerAndBank();

    $webhook = app(StoreWebhookAction::class)->handle($user, $bankAccount, [
        'name' => 'All credits',
        'url' => 'https://example.com/webhook',
        'event_keyword' => '',
        'status' => 'active',
    ]);

    expect($webhook->event_keyword)->toBeNull()
        ->and($webhook->fresh()->event_keyword)->toBeNull();
});

test('dispatch webhook action can queue webhook with empty event keyword', function () {
    [$user, $bankAccount] = createWebhookOwnerAndBank();

    $webhook = app(StoreWebhookAction::class)->handle($user, $bankAccount, [
        'name' => 'All credits',
        'url' => 'https://example.com/webhook',
        'event_keyword' => '',
        'status' => 'active',
    ]);

    $count = app(DispatchWebhookAction::class)->handle(
        $user,
        $bankAccount,
        '',
        ['source' => 'test'],
    );

    expect($count)->toBe(1);

    Queue::assertPushed(DispatchWebhookJob::class, fn (DispatchWebhookJob $job): bool => $job->webhookId === $webhook->id && $job->eventKeyword === '');
});

test('same url can be used for different events but not duplicated with the same event', function () {
    [$user, $bankAccount] = createWebhookOwnerAndBank();
    $action = app(StoreWebhookAction::class);

    $action->handle($user, $bankAccount, [
        'name' => 'Event 1',
        'url' => 'https://example.com/webhook',
        'event_keyword' => 'napabc',
        'status' => 'active',
    ]);

    $secondWebhook = $action->handle($user, $bankAccount, [
        'name' => 'Event 2',
        'url' => 'https://example.com/webhook',
        'event_keyword' => 'order123',
        'status' => 'active',
    ]);

    expect($secondWebhook)->toBeInstanceOf(Webhook::class)
        ->and(Webhook::query()->count())->toBe(2);

    expect(fn () => $action->handle($user, $bankAccount, [
        'name' => 'Duplicate',
        'url' => 'https://example.com/webhook',
        'event_keyword' => 'napabc',
        'status' => 'active',
    ]))->toThrow(ApiException::class, 'URL webhook này đã được cấu hình với event tương ứng.');
});

test('same url cannot be duplicated with empty event marker', function () {
    [$user, $bankAccount] = createWebhookOwnerAndBank();
    $action = app(StoreWebhookAction::class);

    $action->handle($user, $bankAccount, [
        'name' => 'All credits',
        'url' => 'https://example.com/all',
        'event_keyword' => '',
        'status' => 'active',
    ]);

    expect(fn () => $action->handle($user, $bankAccount, [
        'name' => 'All credits duplicate',
        'url' => 'https://example.com/all',
        'event_keyword' => '',
        'status' => 'active',
    ]))->toThrow(ApiException::class, 'URL webhook này đã được cấu hình với event tương ứng.');
});

test('update webhook cannot change into an existing url and event combination', function () {
    [$user, $bankAccount] = createWebhookOwnerAndBank();
    $storeAction = app(StoreWebhookAction::class);
    $updateAction = app(UpdateWebhookAction::class);

    $firstWebhook = $storeAction->handle($user, $bankAccount, [
        'name' => 'First',
        'url' => 'https://example.com/webhook',
        'event_keyword' => 'napabc',
        'status' => 'active',
    ]);

    $secondWebhook = $storeAction->handle($user, $bankAccount, [
        'name' => 'Second',
        'url' => 'https://example.com/webhook',
        'event_keyword' => 'order123',
        'status' => 'active',
    ]);

    expect(fn () => $updateAction->handle($secondWebhook, [
        'name' => 'Second',
        'url' => 'https://example.com/webhook',
        'event_keyword' => 'napabc',
        'status' => 'active',
    ]))->toThrow(ApiException::class, 'URL webhook này đã được cấu hình với event tương ứng.');

    $updated = $updateAction->handle($firstWebhook, [
        'name' => 'First updated',
        'url' => 'https://example.com/webhook',
        'event_keyword' => 'napabc',
        'status' => 'inactive',
    ]);

    expect($updated->status)->toBe('inactive');
});
