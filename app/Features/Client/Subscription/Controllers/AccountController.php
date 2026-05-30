<?php

namespace App\Features\Client\Subscription\Controllers;

use App\Features\Client\Subscription\Requests\StoreAccountRequest;
use App\Features\Client\Subscription\Services\AccountProvisioningService;
use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountProvisioningService $accountProvisioningService,
    ) {}

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $subscription = UserSubscription::query()->findOrFail($request->integer('subscription_id'));

        $this->authorize('manage', $subscription);

        $account = $this->accountProvisioningService->createAccount($request->user(), $subscription);

        return response()->json([
            'status' => true,
            'message' => 'Tạo resource/account thành công.',
            'data' => $account->fresh(['subscription']),
        ], 201);
    }
}
