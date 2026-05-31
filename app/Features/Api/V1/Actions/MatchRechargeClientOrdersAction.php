<?php

namespace App\Features\Api\V1\Actions;

use App\Models\BankAccount;
use App\Models\RechargeClient;
use Illuminate\Support\Facades\DB;

class MatchRechargeClientOrdersAction
{
    /**
     * @param  list<array<string, mixed>>  $transactions
     */
    public function handle(BankAccount $bankAccount, array $transactions): int
    {
        return $this->handleWithMatches($bankAccount, $transactions)['matched_count'];
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     * @return array{matched_count:int,matched_orders:array<string,RechargeClient>}
     */
    public function handleWithMatches(BankAccount $bankAccount, array $transactions): array
    {
        $matchedCount = 0;
        $matchedOrders = [];

        foreach ($transactions as $transaction) {
            if (($transaction['type'] ?? null) !== 'credit') {
                continue;
            }

            $description = trim((string) ($transaction['description'] ?? ''));
            $amount = round((float) ($transaction['amount'] ?? 0), 2);

            if ($description === '' || $amount <= 0) {
                continue;
            }

            $candidateOrders = RechargeClient::query()
                ->where('bank_account_id', $bankAccount->id)
                ->whereIn('status', [RechargeClient::STATUS_PENDING, RechargeClient::STATUS_PROCESSING])
                ->where('amount', $amount)
                ->where(function ($query): void {
                    $query
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->orderBy('requested_at')
                ->orderBy('id')
                ->get();

            $matchedOrder = $candidateOrders->first(function (RechargeClient $order) use ($description): bool {
                return str_contains(mb_strtolower($description), mb_strtolower((string) $order->transfer_content));
            });

            if (! $matchedOrder instanceof RechargeClient) {
                continue;
            }

            $savedOrder = null;

            DB::transaction(function () use ($matchedOrder, $transaction, &$matchedCount, &$savedOrder): void {
                /** @var RechargeClient|null $lockedOrder */
                $lockedOrder = RechargeClient::query()
                    ->with('apiKey')
                    ->whereKey($matchedOrder->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedOrder instanceof RechargeClient) {
                    return;
                }

                if (! in_array($lockedOrder->status, [RechargeClient::STATUS_PENDING, RechargeClient::STATUS_PROCESSING], true)) {
                    return;
                }

                $lockedOrder->forceFill([
                    'status' => RechargeClient::STATUS_PAID,
                    'paid_at' => $transaction['transaction_time'] ?? now(),
                    'matched_bank_transaction_id' => $transaction['id'] ?? null,
                    'metadata' => array_merge($lockedOrder->metadata ?? [], [
                        'matched_transaction_id' => $transaction['transaction_id'] ?? null,
                        'matched_transaction_time' => $transaction['transaction_time'] ?? null,
                    ]),
                ])->save();

                $savedOrder = $lockedOrder->fresh(['apiKey']);
                $matchedCount++;
            });

            if ($savedOrder instanceof RechargeClient) {
                $matchedOrders[$this->transactionKey($transaction)] = $savedOrder;
            }
        }

        return [
            'matched_count' => $matchedCount,
            'matched_orders' => $matchedOrders,
        ];
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    public function transactionMatches(RechargeClient $order, array $transaction): bool
    {
        $description = trim((string) ($transaction['description'] ?? ''));
        $amount = round((float) ($transaction['amount'] ?? 0), 2);

        return ($transaction['type'] ?? null) === 'credit'
            && $description !== ''
            && $amount > 0
            && $amount === round((float) $order->amount, 2)
            && str_contains(mb_strtolower($description), mb_strtolower((string) $order->transfer_content));
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function transactionKey(array $transaction): string
    {
        if (isset($transaction['id'])) {
            return 'id:'.(string) $transaction['id'];
        }

        return 'tx:'.(string) ($transaction['transaction_id'] ?? '');
    }
}
