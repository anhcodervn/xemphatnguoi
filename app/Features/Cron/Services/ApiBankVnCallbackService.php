<?php

namespace App\Features\Cron\Services;

use App\Models\RechargeOrder;
use App\Models\UserLog;
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
                'message' => 'Thieu thong tin xac thuc callback.',
                'data' => [
                    'reason' => 'missing_signature_fields',
                ],
            ];
        }

        $webhook = Webhook::query()
            ->where('bank_account_id', $bankId)
            ->where('secret_key', $normalizedSecretKey)
            ->where('status', 'active')
            ->first();

        if (! $webhook instanceof Webhook) {
            return [
                'ok' => false,
                'status_code' => 403,
                'message' => 'Webhook secret khong hop le hoac da ngung hoat dong.',
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
                'message' => 'Chu ky callback khong hop le.',
                'data' => [
                    'reason' => 'invalid_signature',
                ],
            ];
        }

        return [
            'ok' => true,
            'status_code' => 200,
            'message' => 'Signature hop le.',
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
                        'message' => 'Lenh nap nay da duoc xu ly truoc do.',
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
                'description' => 'Webhook callback approved recharge '.$lockedOrder->order_code,
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

            UserLog::query()->create([
                'user_id' => (int) $lockedOrder->user_id,
                'action' => 'recharge_order_paid',
                'description' => sprintf('Yeu cau nap %s da duoc xac nhan qua callback webhook', $lockedOrder->order_code),
                'ip' => $ip,
                'user_agent' => (string) $userAgent,
            ]);

            return [
                'status_code' => 200,
                'body' => [
                    'status' => true,
                    'message' => 'Da xac nhan lenh nap thanh cong.',
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
