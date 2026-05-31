<?php

namespace App\Features\Client\Bank\Services;

use App\Exceptions\ApiException;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Service\ApiBank\ACB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AcbService
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
                'bank_name' => 'acb',
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

            if (($login['status'] ?? null) !== 'success') {
                throw new ApiException(
                    (string) ($login['message'] ?? 'Đăng nhập ACB thất bại.'),
                    422,
                    [
                        'errors' => [
                            'username' => [(string) ($login['message'] ?? 'Đăng nhập ACB thất bại.')],
                        ],
                        'data' => $login['data'] ?? [],
                    ],
                );
            }

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
            return [
                ...(new ACB($bankAccount))->LSGD($rows),
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
        return (new ACB($bankAccount))->login();
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

    protected function cooldownSeconds(): int
    {
        return (int) config('bank-sync.providers.acb.cooldown_seconds', 10);
    }

    protected function lockSeconds(): int
    {
        return (int) config('bank-sync.providers.acb.lock_seconds', 20);
    }

    protected function syncLockKey(BankAccount $bankAccount): string
    {
        return "bank-sync:acb:{$bankAccount->id}";
    }
}
