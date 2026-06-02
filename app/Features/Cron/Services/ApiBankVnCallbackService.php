<?php

namespace App\Features\Cron\Services;

use App\Jobs\SaveUserLogJob;
use App\Models\RechargeOrder;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Webhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiBankVnCallbackService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     ok:bool,
     *     status_code:int,
     *     message:string,
     *     data:array<string,mixed>,
     *     credentials?:array{webhook:Webhook,bank_id:int}
     * }
     */
    public function verifyCallbackSignature(array $payload, ?string $secretKey): array
    {
        $sign = Str::lower(trim((string) ($payload['sign'] ?? '')));
        $bankId = max(0, (int) ($payload['bank_id'] ?? 0));
        $normalizedSecretKey = trim((string) $secretKey);

        if ($normalizedSecretKey === '' || $sign === '' || $bankId <= 0) {
            return [
                'ok' => false,
                'status_code' => 422,
                'message' => 'Thiếu thông tin xác thực callback.',
                'data' => [
                    'reason' => 'missing_signature_fields',
                ],
            ];
        }

        $webhook = Webhook::query()
            ->where('bank_account_id', $bankId)
            ->where('status', 'active')
            ->get()
            ->first(fn (Webhook $candidate): bool => hash_equals((string) $candidate->secret_key, $normalizedSecretKey));

        if (! $webhook instanceof Webhook) {
            return [
                'ok' => false,
                'status_code' => 403,
                'message' => 'Webhook secret không hợp lệ hoặc đã ngừng hoạt động.',
                'data' => [
                    'reason' => 'invalid_webhook_secret',
                ],
            ];
        }

        $expectedSign = md5($normalizedSecretKey.(string) $bankId);

        if (! hash_equals($expectedSign, $sign)) {
            return [
                'ok' => false,
                'status_code' => 401,
                'message' => 'Chữ ký callback không hợp lệ.',
                'data' => [
                    'reason' => 'invalid_signature',
                ],
            ];
        }

        return [
            'ok' => true,
            'status_code' => 200,
            'message' => 'Chữ ký hợp lệ.',
            'data' => [],
            'credentials' => [
                'webhook' => $webhook,
                'bank_id' => $bankId,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractTransactionDescription(array $payload): string
    {
        $candidates = [
            $payload['description'] ?? null,
            $payload['content'] ?? null,
            $payload['message'] ?? null,
            data_get($payload, 'data.description'),
            data_get($payload, 'data.content'),
            data_get($payload, 'data.message'),
            data_get($payload, 'payload.description'),
            data_get($payload, 'payload.content'),
            data_get($payload, 'payload.message'),
            data_get($payload, 'transaction.description'),
            data_get($payload, 'transaction.content'),
            data_get($payload, 'data.transaction.description'),
            data_get($payload, 'data.transaction.content'),
            data_get($payload, 'payload.transaction.description'),
            data_get($payload, 'payload.transaction.content'),
            data_get($payload, 'payload.transaction.raw_data.description'),
            data_get($payload, 'payload.transaction.raw_data.content'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractTransactionAmount(array $payload): ?float
    {
        $candidates = [
            $payload['amount'] ?? null,
            data_get($payload, 'data.amount'),
            data_get($payload, 'payload.amount'),
            data_get($payload, 'transaction.amount'),
            data_get($payload, 'data.transaction.amount'),
            data_get($payload, 'payload.transaction.amount'),
            data_get($payload, 'payload.transaction.raw_data.amount'),
            data_get($payload, 'payload.transaction.raw_data.creditAmount'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (float) $candidate;
            }

            if (is_string($candidate) && trim($candidate) !== '') {
                $normalized = str_replace([',', ' '], '', $candidate);

                if (is_numeric($normalized)) {
                    return (float) $normalized;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function transactionIsOutgoing(array $payload): bool
    {
        $typeCandidates = [
            $payload['type'] ?? null,
            data_get($payload, 'data.type'),
            data_get($payload, 'payload.type'),
            data_get($payload, 'transaction.type'),
            data_get($payload, 'data.transaction.type'),
            data_get($payload, 'payload.transaction.type'),
        ];

        foreach ($typeCandidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $normalized = Str::lower(trim($candidate));

            if (in_array($normalized, ['debit', 'outgoing', 'withdraw', 'sent'], true)) {
                return true;
            }
        }

        $directionCandidates = [
            $payload['direction'] ?? null,
            $payload['cd'] ?? null,
            data_get($payload, 'data.direction'),
            data_get($payload, 'data.cd'),
            data_get($payload, 'payload.direction'),
            data_get($payload, 'payload.cd'),
            data_get($payload, 'transaction.direction'),
            data_get($payload, 'transaction.cd'),
            data_get($payload, 'data.transaction.direction'),
            data_get($payload, 'data.transaction.cd'),
            data_get($payload, 'payload.transaction.direction'),
            data_get($payload, 'payload.transaction.cd'),
            data_get($payload, 'payload.transaction.raw_data.DorCCode'),
            data_get($payload, 'payload.transaction.raw_data.CD'),
        ];

        foreach ($directionCandidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $normalized = Str::upper(trim($candidate));

            if (in_array($normalized, ['D', '-', 'OUT'], true)) {
                return true;
            }
        }

        return false;
    }

    public function findMatchingRechargeOrder(string $description, float $amount): ?RechargeOrder
    {
        $normalizedDescription = $this->normalizeText($description);
        $normalizedAmount = number_format($amount, 2, '.', '');

        return RechargeOrder::query()
            ->whereIn('status', [RechargeOrder::STATUS_PENDING, RechargeOrder::STATUS_PROCESSING])
            ->where('total_amount', $normalizedAmount)
            ->orderByDesc('id')
            ->get()
            ->first(function (RechargeOrder $rechargeOrder) use ($normalizedDescription): bool {
                $normalizedOrderCode = $this->normalizeText((string) $rechargeOrder->order_code);

                if ($normalizedDescription === '' || $normalizedOrderCode === '') {
                    return false;
                }

                return mb_strpos($normalizedDescription, $normalizedOrderCode) !== false;
            });
    }

    protected function normalizeText(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status_code:int,body:array<string,mixed>}
     */
    public function approveRechargeOrder(int $orderId, array $payload, string $description, ?string $ip, ?string $userAgent): array
    {
        return DB::transaction(function () use ($orderId, $payload, $description, $ip, $userAgent): array {
            $lockedOrder = RechargeOrder::query()
                ->with('user')
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedOrder->status, [RechargeOrder::STATUS_PENDING, RechargeOrder::STATUS_PROCESSING], true)) {
                return [
                    'status_code' => 200,
                    'body' => [
                        'status' => true,
                        'message' => 'Lệnh nạp đã được xử lý trước đó.',
                        'data' => [
                            'ignored' => true,
                            'reason' => 'already_processed',
                            'order_code' => $lockedOrder->order_code,
                            'current_status' => $lockedOrder->status,
                        ],
                    ],
                ];
            }

            $wallet = Wallet::query()
                ->where('user_id', $lockedOrder->user_id)
                ->where('type', Wallet::TYPE_MAIN)
                ->lockForUpdate()
                ->first();

            if (! $wallet instanceof Wallet) {
                $wallet = $lockedOrder->user->wallets()->create([
                    'type' => Wallet::TYPE_MAIN,
                    'balance' => 0,
                    'hold_balance' => 0,
                    'total_recharge' => 0,
                    'total_spent' => 0,
                ]);

                $wallet = Wallet::query()
                    ->whereKey($wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $creditAmount = (float) $lockedOrder->total_amount;
            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + $creditAmount;

            $wallet->forceFill([
                'balance' => $balanceAfter,
                'total_recharge' => (float) $wallet->total_recharge + (float) $lockedOrder->amount,
            ])->save();

            $walletTransaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $creditAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => RechargeOrder::class,
                'reference_id' => $lockedOrder->id,
                'description' => 'Nạp tiền tự động thành công mã GD:'.$lockedOrder->order_code,
                'status' => 'success',
            ]);

            $metadata = is_array($lockedOrder->metadata) ? $lockedOrder->metadata : [];
            $metadata['callback_apibankvn'] = [
                'description' => $description,
                'received_at' => now()->toISOString(),
                'payload' => $payload,
            ];

            $lockedOrder->forceFill([
                'status' => RechargeOrder::STATUS_PAID,
                'paid_at' => $lockedOrder->paid_at ?? now(),
                'metadata' => $metadata,
            ])->save();

            SaveUserLogJob::dispatch(
                userId: (int) $lockedOrder->user_id,
                action: 'recharge_order_paid',
                description: sprintf('Yêu cầu nạp %s đã được xác nhận qua callback webhook', $lockedOrder->order_code),
                ip: $ip,
                userAgent: (string) $userAgent,
            )->onQueue('user-logs')->afterCommit();

            return [
                'status_code' => 200,
                'body' => [
                    'status' => true,
                    'message' => 'Đã xác nhận lệnh nạp thành công.',
                    'data' => [
                        'ignored' => false,
                        'order_code' => $lockedOrder->order_code,
                        'status' => $lockedOrder->status,
                        'wallet_transaction_id' => $walletTransaction->id,
                    ],
                ],
            ];
        });
    }
}
