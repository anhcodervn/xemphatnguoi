<?php

use App\Features\Client\ApiKey\Services\ApiKeyEligibilityService;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;
use Tests\TestCase;

uses(TestCase::class);

it('allows api key creation when user has an active unexpired subscription', function () {
    $user = new User();
    $subscription = new UserSubscription([
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(7),
    ]);

    $user->setRelation('userSubscriptions', collect([$subscription]));

    $service = new ApiKeyEligibilityService();

    expect($service->canCreate($user))->toBeTrue();
});

it('blocks api key creation when subscription is expired', function () {
    $user = new User();
    $subscription = new UserSubscription([
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subMinute(),
    ]);

    $user->setRelation('userSubscriptions', collect([$subscription]));

    $service = new ApiKeyEligibilityService();

    expect($service->canCreate($user))->toBeFalse();
});

it('blocks api key creation when no active subscription exists', function () {
    $user = new User();
    $subscription = new UserSubscription([
        'status' => SubscriptionStatus::Cancelled,
        'expires_at' => now()->addDays(7),
    ]);

    $user->setRelation('userSubscriptions', collect([$subscription]));

    $service = new ApiKeyEligibilityService();

    expect($service->canCreate($user))->toBeFalse();
});
