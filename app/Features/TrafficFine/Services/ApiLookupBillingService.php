<?php

namespace App\Features\TrafficFine\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\User;
use App\Support\SettingStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLookupBillingService
{
    public const PRICE_SETTING_KEY = 'traffic_fine_api_request_price';

    public const ATTRIBUTE_UNIT_PRICE = 'api_billing_unit_price';

    public const ATTRIBUTE_CHARGED_AMOUNT = 'api_billing_charged_amount';

    public const ATTRIBUTE_STATUS = 'api_billing_status';

    public const ATTRIBUTE_TRANSACTION_ID = 'api_billing_transaction_id';

    public const ATTRIBUTE_LOG_ID = 'api_billing_log_id';

    public function __construct(
        private readonly SettingStore $settingStore,
        private readonly WalletService $walletService,
    ) {}

    public function pricePerRequest(): int
    {
        $defaultPrice = max(1, (int) config('traffic-fines.billing.api_request_price', 20));
        $configuredPrice = filter_var(
            $this->settingStore->getString(self::PRICE_SETTING_KEY, (string) $defaultPrice),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 1_000_000]],
        );

        return $configuredPrice === false ? $defaultPrice : $configuredPrice;
    }

    public function ensureSufficientBalance(Request $request, User $user): void
    {
        $price = $this->pricePerRequest();
        $wallet = $this->walletService->getWallet($user);

        $this->setAttribute($request, self::ATTRIBUTE_UNIT_PRICE, $price);
        $this->setAttribute($request, self::ATTRIBUTE_CHARGED_AMOUNT, 0);
        $this->setAttribute($request, self::ATTRIBUTE_STATUS, 'not_charged');

        if ((float) $wallet->balance < $price) {
            $this->setAttribute($request, self::ATTRIBUTE_STATUS, 'insufficient_balance');

            throw new ApiException('Số dư ví không đủ để thực hiện tra cứu API.', 402, [
                'code' => 'insufficient_balance',
                'required_amount' => $price,
                'balance' => (string) $wallet->balance,
            ]);
        }
    }

    public function charge(Request $request, User $user, ApiKey $apiKey): void
    {
        $price = (int) $request->attributes->get(self::ATTRIBUTE_UNIT_PRICE, $this->pricePerRequest());

        try {
            [$transaction, $apiLog] = DB::transaction(function () use ($request, $user, $apiKey, $price): array {
                $transaction = $this->walletService->debitWithTransaction(
                    user: $user,
                    amount: $price,
                    referenceType: 'traffic_fine_api_request',
                    referenceId: $apiKey->id,
                    description: sprintf(
                        'Phí tra cứu API biển số %s',
                        mb_strtoupper($request->string('plate')->toString()),
                    ),
                );

                $apiLog = ApiLog::query()->create([
                    'user_id' => $user->id,
                    'api_key_id' => $apiKey->id,
                    'wallet_transaction_id' => $transaction->id,
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'request_data' => [
                        'query' => [
                            'plate' => $request->string('plate')->toString(),
                            'vehicle_type' => $request->string('vehicle_type')->toString(),
                        ],
                        'body' => [],
                        'route' => [],
                    ],
                    'service_response_data' => $request->attributes->get('service_response_data'),
                    'response_data' => null,
                    'status_code' => 200,
                    'response_time_ms' => 0,
                    'unit_price' => $price,
                    'charged_amount' => $price,
                    'billing_status' => 'charged',
                    'created_at' => now(),
                ]);

                return [$transaction, $apiLog];
            });
        } catch (ApiException) {
            $this->setAttribute($request, self::ATTRIBUTE_STATUS, 'insufficient_balance');

            throw new ApiException('Số dư ví không đủ để thực hiện tra cứu API.', 402, [
                'code' => 'insufficient_balance',
                'required_amount' => $price,
            ]);
        }

        $this->setAttribute($request, self::ATTRIBUTE_TRANSACTION_ID, $transaction->id);
        $this->setAttribute($request, self::ATTRIBUTE_LOG_ID, $apiLog->id);
        $this->setAttribute($request, self::ATTRIBUTE_CHARGED_AMOUNT, $price);
        $this->setAttribute($request, self::ATTRIBUTE_STATUS, 'charged');
    }

    private function setAttribute(Request $request, string $key, mixed $value): void
    {
        $request->attributes->set($key, $value);

        $baseRequest = app('request');
        if ($baseRequest instanceof Request && $baseRequest !== $request) {
            $baseRequest->attributes->set($key, $value);
        }
    }
}
