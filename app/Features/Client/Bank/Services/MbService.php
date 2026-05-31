<?php

namespace App\Features\Client\Bank\Services;

use App\Exceptions\ApiException;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Service\ApiBank\MbBank;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MbService
{
    /**
     * @param  array{
     *     bank_code: string,
     *     display_name: string,
     *     username: string,
     *     password?: ?string,
     *     account_number: string
     * }  $data
     */
    public function saveBank(User $user, array $data, ?BankAccount $bankAccount = null): BankAccount
    {
        return DB::transaction(function () use ($user, $data, $bankAccount): BankAccount {
            $targetBankAccount = $bankAccount ?? new BankAccount;

            $targetBankAccount->forceFill([
                'user_id' => $user->id,
                'bank_name' => 'mb',
                'account_name' => $data['display_name'],
                'account_number' => $data['account_number'],
                'username' => $data['username'],
                'status' => 'active',
            ]);

            if (array_key_exists('password', $data) && filled($data['password'])) {
                $targetBankAccount->password = $data['password'];
            }

            $targetBankAccount->save();

            $login = $this->login($targetBankAccount);
            // dd($login);
            if (($login['status'] ?? null) !== 'success') {
                throw new ApiException(
                    (string) ($login['message'] ?? 'Đăng nhập MB thất bại.'),
                    422,
                    [
                        'errors' => [
                            'username' => [(string) ($login['message'] ?? 'Đăng nhập MB thất bại.')],
                        ],
                        'data' => $login['data'] ?? [],
                    ],
                );
            }

            $resolvedAccountName = (string) data_get($login, 'data.cust.nm', $targetBankAccount->account_name);
            if ($resolvedAccountName !== '') {
                $targetBankAccount->account_name = $resolvedAccountName;
            }

            $targetBankAccount->save();

            return $targetBankAccount->fresh() ?? $targetBankAccount;
        });
    }

    /**
     * @return array{status?: string, message?: string, data?: mixed, meta?: array<string, mixed>}
     */
    public function fetchTransactions(BankAccount $bankAccount, int $rows = 20, bool $forceRefresh = false): array
    {
        if (! $forceRefresh && $this->shouldUseStoredTransactions($bankAccount)) {
            return $this->storedTransactionsResponse(
                $bankAccount,
                $rows,
                'Đang trong thời gian chờ đồng bộ. Hệ thống trả về dữ liệu giao dịch đã lưu trong database.',
            );
        }

        $lock = Cache::lock($this->syncLockKey($bankAccount), $this->lockSeconds());
        if (! $lock->get()) {
            return $this->storedTransactionsResponse(
                $bankAccount,
                $rows,
                'Yêu cầu đồng bộ giao dịch đang được xử lý. Hệ thống trả về dữ liệu hiện có trong database.',
            );
        }

        try {
            $result = $this->buildClient($bankAccount)->getTransactionHistory((string) $bankAccount->account_number, $rows, 7);

            return [
                'status' => (string) ($result['status'] ?? 'error'),
                'message' => (string) ($result['message'] ?? ''),
                'data' => $result['data'] ?? ['transactions' => []],
                'meta' => [
                    'from_cache' => false,
                    'cooldown_seconds' => $this->cooldownSeconds(),
                ],
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{status?: string, message?: string, data?: mixed}
     */
    protected function login(BankAccount $bankAccount): array
    {
        $response = $this->buildClient($bankAccount)->doLogin();

        if (! is_array($response)) {
            return [
                'status' => 'error',
                'message' => 'Đăng nhập MB thất bại.',
                'data' => $response,
            ];
        }

        return [
            'status' => (($response['success'] ?? false) === true) ? 'success' : 'error',
            'message' => (string) ($response['message'] ?? ''),
            'data' => $response['data'] ?? $response,
        ];
    }

    protected function shouldUseStoredTransactions(BankAccount $bankAccount): bool
    {
        if (! $bankAccount->last_sync_at instanceof Carbon) {
            return false;
        }

        return $bankAccount->last_sync_at
            ->copy()
            ->addSeconds($this->cooldownSeconds())
            ->isFuture();
    }

    /**
     * @return array{status: string, message: string, data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    protected function storedTransactionsResponse(BankAccount $bankAccount, int $rows, string $message): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $this->storedTransactions($bankAccount, $rows),
            'meta' => [
                'from_cache' => true,
                'cooldown_seconds' => $this->cooldownSeconds(),
                'next_sync_at' => $bankAccount->last_sync_at?->copy()->addSeconds($this->cooldownSeconds())?->toDateTimeString(),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function storedTransactions(BankAccount $bankAccount, int $rows): array
    {
        return BankTransaction::query()
            ->whereBelongsTo($bankAccount)
            ->orderByDesc('transaction_time')
            ->orderByDesc('id')
            ->limit($rows)
            ->get()
            ->map(fn (BankTransaction $transaction) => [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id,
                'amount' => (string) $transaction->amount,
                'description' => $transaction->description,
                'transaction_time' => $transaction->transaction_time?->toDateTimeString(),
                'type' => $transaction->type,
                'raw_data' => $transaction->raw_data,
                'created_at' => $transaction->created_at?->toDateTimeString(),
                'updated_at' => $transaction->updated_at?->toDateTimeString(),
            ])
            ->all();
    }

    protected function buildClient(BankAccount $bankAccount): MbBank
    {
        return new MbBank(
            (string) $bankAccount->username,
            (string) $bankAccount->password,
        );
    }

    protected function cooldownSeconds(): int
    {
        return (int) config('bank-sync.providers.mb.cooldown_seconds', 6);
    }

    protected function lockSeconds(): int
    {
        return (int) config('bank-sync.providers.mb.lock_seconds', 15);
    }

    protected function syncLockKey(BankAccount $bankAccount): string
    {
        return "bank-sync:mb:{$bankAccount->id}";
    }
}
