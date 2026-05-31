<?php

namespace App\Jobs;

use App\Features\Api\V1\Actions\MatchRechargeClientOrdersAction;
use App\Features\Client\Bank\Actions\TransactionBankAction;
use App\Features\Client\Package\Services\PackageService;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBankAccountTransactionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 50;

    public function __construct(
        public int $bankAccountId,
        public int $transactionLimit = 20,
    ) {}

    public function handle(
        TransactionBankAction $action,
        MatchRechargeClientOrdersAction $matchRechargeClientOrdersAction,
        PackageService $packageService,
    ): void {
        $bankAccount = BankAccount::query()->find($this->bankAccountId);

        if (! $bankAccount instanceof BankAccount) {
            return;
        }

        if ($bankAccount->status !== 'active') {
            return;
        }

        $owner = $bankAccount->user;

        if (! $owner instanceof User) {
            return;
        }

        if ($packageService->getActiveSubscription($owner) === null) {
            return;
        }

        try {
            $result = $action->handleWithChanges($bankAccount, $this->transactionLimit);
            $newTransactions = $result['new_transactions'] ?? [];
            $matchedRechargeClients = $matchRechargeClientOrdersAction->handle($bankAccount, $newTransactions);

            $this->dispatchWebhooksForNewTransactions($bankAccount, $newTransactions);

            Log::info('Bank transaction sync completed.', [
                'bank_account_id' => $bankAccount->id,
                'bank_name' => $bankAccount->bank_name,
                'transactions_count' => count($result['transactions'] ?? []),
                'new_transactions_count' => count($newTransactions),
                'matched_recharge_clients_count' => $matchedRechargeClients,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Bank transaction sync failed.', [
                'bank_account_id' => $bankAccount->id,
                'bank_name' => $bankAccount->bank_name,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $newTransactions
     */
    protected function dispatchWebhooksForNewTransactions(BankAccount $bankAccount, array $newTransactions): void
    {
        if ($newTransactions === []) {
            return;
        }

        $webhooks = Webhook::query()
            ->where('bank_account_id', $bankAccount->id)
            ->where('status', 'active')
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        foreach ($newTransactions as $transaction) {
            if (($transaction['type'] ?? null) !== 'credit') {
                continue;
            }

            $description = strtolower(trim((string) ($transaction['description'] ?? '')));

            if ($description === '') {
                continue;
            }

            foreach ($webhooks as $webhook) {
                if ($this->shouldDispatchAllTransactions($webhook->event_keyword)) {
                    DispatchWebhookJob::dispatch($webhook->id, 'bank.transaction', [
                        'source' => 'cron.bank-sync',
                        'bank_account_id' => $bankAccount->id,
                        'transaction' => $transaction,
                    ]);

                    continue;
                }

                $eventKeyword = strtolower(trim((string) $webhook->event_keyword));

                if (str_contains($description, $eventKeyword)) {
                    DispatchWebhookJob::dispatch($webhook->id, $eventKeyword, [
                        'source' => 'cron.bank-sync',
                        'bank_account_id' => $bankAccount->id,
                        'transaction' => $transaction,
                    ]);
                }
            }
        }
    }

    protected function shouldDispatchAllTransactions(mixed $eventKeyword): bool
    {
        if ($eventKeyword === null) {
            return true;
        }

        return is_string($eventKeyword) && trim($eventKeyword) === '';
    }
}
