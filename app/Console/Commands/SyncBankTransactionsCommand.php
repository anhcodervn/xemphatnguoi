<?php

namespace App\Console\Commands;

use App\Jobs\SyncBankAccountTransactionsJob;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SyncBankTransactionsCommand extends Command
{
    protected $signature = 'bank:sync-transactions
        {--bank= : Sync only one bank code}
        {--transaction-limit=20 : Number of newest transactions to sync per account}
        {--max-accounts-per-bank=0 : Override bank limit (0 = use bank setting)}';

    protected $description = 'Dispatch sync jobs for active bank accounts using bank-level request-per-minute limits.';

    public function handle(): int
    {
        $bankFilter = strtolower(trim((string) $this->option('bank')));
        $transactionLimit = max((int) $this->option('transaction-limit'), 10);
        $maxAccountsOverride = max((int) $this->option('max-accounts-per-bank'), 0);

        $lock = Cache::lock('bank-sync:command:dispatch', 50);
        if (! $lock->get()) {
            $this->warn('Sync command is already running.');

            return self::SUCCESS;
        }

        try {
            $banks = Bank::query()
                ->where('is_active', true)
                ->when($bankFilter !== '', fn ($query) => $query->where('code', $bankFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($banks->isEmpty()) {
                $this->warn('No active banks matched for sync dispatch.');

                return self::SUCCESS;
            }

            foreach ($banks as $bank) {
                $limitPerMinute = $maxAccountsOverride > 0
                    ? $maxAccountsOverride
                    : $this->resolveLimitPerMinute($bank);

                $accounts = $this->accountsForBank($bank->code, $limitPerMinute);
                if ($accounts->isEmpty()) {
                    $this->line("[$bank->code] no active bank accounts to dispatch.");

                    continue;
                }

                $intervalSeconds = max((int) floor(60 / max($limitPerMinute, 1)), 1);
                $maxDispatchCount = min($accounts->count(), $limitPerMinute);

                $accounts
                    ->take($maxDispatchCount)
                    ->values()
                    ->each(function (BankAccount $account, int $index) use ($intervalSeconds, $transactionLimit, $bank): void {
                        SyncBankAccountTransactionsJob::dispatch($account->id, $transactionLimit)
                            ->delay(now()->addSeconds($index * $intervalSeconds))
                            ->onQueue('bank-sync');

                        $this->line(sprintf(
                            '[%s] account_id=%d queued with delay=%ss',
                            $bank->code,
                            $account->id,
                            $index * $intervalSeconds
                        ));
                    });
            }
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    protected function resolveLimitPerMinute(Bank $bank): int
    {
        $fromBankConfig = (int) $bank->limit_request_per_minute;
        if ($fromBankConfig > 0) {
            return $fromBankConfig;
        }

        $fallback = (int) config("bank-sync.providers.{$bank->code}.requests_per_minute", 6);

        return max($fallback, 1);
    }

    /**
     * @return Collection<int, BankAccount>
     */
    protected function accountsForBank(string $bankCode, int $limit): Collection
    {
        return BankAccount::query()
            ->where('bank_name', $bankCode)
            ->where('status', 'active')
            ->whereHas('user.userSubscriptions', function ($query): void {
                $query
                    ->where('status', SubscriptionStatus::Active)
                    ->where('expires_at', '>', now());
            })
            ->orderByRaw('last_sync_at IS NULL DESC')
            ->orderBy('last_sync_at')
            ->orderBy('id')
            ->limit(max($limit, 1))
            ->get();
    }
}
