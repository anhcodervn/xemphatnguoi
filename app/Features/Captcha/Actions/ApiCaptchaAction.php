<?php

namespace App\Features\Captcha\Actions;

use App\Exceptions\ApiException;
use App\Features\Captcha\Services\CaptchaTaskService;
use App\Features\Client\Package\Services\PackageService;
use App\Features\Client\Subscription\Services\SubscriptionQuotaService;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\ApiKey;
use App\Models\CaptchaService;
use App\Models\CaptchaTask;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;

class ApiCaptchaAction
{
    public function __construct(
        private readonly CaptchaTaskService $captchaTaskService,
        private readonly PackageService $packageService,
        private readonly SubscriptionQuotaService $subscriptionQuotaService,
        private readonly WalletService $walletService,
    ) {}

    /**
     * @param  array{service_code:string,task:array,callback_url?:string|null,soft_id?:string|null}  $data
     * @return array<string, mixed>
     */
    public function handle(array $data, User $user, ApiKey $apiKey): array
    {
        $captchaService = CaptchaService::query()
            ->with('source')
            ->where('code', $data['service_code'])
            ->where('is_active', true)
            ->first();

        if (! $captchaService instanceof CaptchaService) {
            throw new ApiException('Loại captcha không tồn tại hoặc đang tạm dừng.', 404);
        }

        $billingContext = $this->resolveBillingContext($user, $apiKey, $captchaService);

        try {
            return DB::transaction(function () use ($user, $apiKey, $captchaService, $data, $billingContext): array {
                $captchaTask = $this->captchaTaskService->createTaskRecord($user, $apiKey, $captchaService, $data, $billingContext);
                $this->captchaTaskService->attachApiLogServiceResponse($captchaTask);

                if ($billingContext['mode'] === 'package') {
                    $subscription = UserSubscription::query()
                        ->whereKey($billingContext['subscription_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->subscriptionQuotaService->consumeCaptcha($subscription, $captchaService->code);
                } else {
                    $this->walletService->debit(
                        user: $user,
                        amount: (float) $captchaService->selling_price,
                        referenceType: CaptchaTask::class,
                        referenceId: $captchaTask->id,
                        description: sprintf(
                            'Thanh toán task captcha %s (%s)',
                            $captchaTask->task_code,
                            $captchaTask->service_code,
                        ),
                    );
                }

                return $this->captchaTaskService->formatCreatedTaskResponse($captchaTask);
            });
        } catch (ApiException $exception) {
            $this->captchaTaskService->attachApiLogServiceException(
                driver: $captchaService->source?->driver ?? 'unknown',
                action: $captchaService->code,
                requestPayload: $data['task'] ?? [],
                exception: $exception,
            );

            throw $exception;
        }
    }

    /**
     * @return array{mode:'wallet'|'package',subscription_id:int|null,quota_consumed:int}
     */
    private function resolveBillingContext(User $user, ApiKey $apiKey, CaptchaService $captchaService): array
    {
        if ($apiKey->isPackageKey()) {
            $subscription = $this->packageService->resolveSubscriptionFromApiKey($apiKey);
            $this->subscriptionQuotaService->ensureCanConsumeCaptcha($subscription, $captchaService->code);

            return [
                'mode' => 'package',
                'subscription_id' => $subscription->id,
                'quota_consumed' => 1,
            ];
        }

        $servicePrice = (float) $captchaService->selling_price;
        $wallet = $this->walletService->getWallet($user);

        if ((float) $wallet->balance < $servicePrice) {
            throw new ApiException('Số dư ví không đủ để tạo task captcha.', 422);
        }

        return [
            'mode' => 'wallet',
            'subscription_id' => null,
            'quota_consumed' => 0,
        ];
    }
}
