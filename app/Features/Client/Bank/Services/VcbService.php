<?php

namespace App\Features\Client\Bank\Services;

use App\Exceptions\ApiException;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Service\ApiBank\vietCombank;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VcbService
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
    public function saveBank(array $data, ?BankAccount $bankAccount = null): BankAccount
    {
        return DB::transaction(function () use ($data, $bankAccount): BankAccount {
            $targetBankAccount = $bankAccount ?? new BankAccount();

            $targetBankAccount->forceFill([
                'bank_name' => 'vcb',
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
                    (string) ($login['message'] ?? 'Đăng nhập VCB thất bại.'),
                    422,
                    [
                        'errors' => [
                            'username' => [(string) ($login['message'] ?? 'Đăng nhập VCB thất bại.')],
                        ],
                        'data' => $login['data'] ?? [],
                    ],
                );
            }

            $resolvedAccountName = (string) data_get($login, 'data.userInfo.cusName', $targetBankAccount->account_name);
            if ($resolvedAccountName !== '') {
                $targetBankAccount->account_name = $resolvedAccountName;
            }

            $resolvedToken = data_get($login, 'data.accessKey', $targetBankAccount->token);
            if (is_string($resolvedToken) && $resolvedToken !== '') {
                $targetBankAccount->token = $resolvedToken;
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
            $client = $this->buildClient($bankAccount);
            $login = $client->doLogin();

            if (! $this->isSuccessfulLogin($login)) {
                return [
                    'status' => 'error',
                    'message' => (string) data_get($login, 'message', 'Đăng nhập VCB thất bại.'),
                    'data' => $login,
                    'meta' => [
                        'from_cache' => false,
                        'cooldown_seconds' => $this->cooldownSeconds(),
                    ],
                ];
            }

            $fromDate = now()->subDays(7)->format('d/m/Y');
            $toDate = now()->format('d/m/Y');
            $history = $client->getTransactionHistory($fromDate, $toDate, (string) $bankAccount->account_number, $rows);
            // dd($history);
            return [
                'status' => 'success',
                'message' => 'Lấy giao dịch VCB thành công.',
                'data' => [
                    'transactions' => $this->extractTransactionsFromHistory($history),
                ],
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
                'message' => 'Đăng nhập VCB thất bại.',
                'data' => $response,
            ];
        }

        return [
            'status' => $this->isSuccessfulLogin($response) ? 'success' : 'error',
            'message' => (string) data_get($response, 'message', ''),
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

    protected function buildClient(BankAccount $bankAccount): vietCombank
    {
        return new vietCombank(
            (string) $bankAccount->username,
            (string) $bankAccount->password,
            (string) $bankAccount->account_number,
        );
    }

    protected function isSuccessfulLogin(mixed $response): bool
    {
        if (! is_array($response)) {
            return false;
        }

        if (($response['success'] ?? false) === true) {
            return true;
        }

        return (string) data_get($response, 'data.code') === '00';
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function extractTransactionsFromHistory(mixed $history): array
    {
        if (is_object($history) && is_array($history->normalized_transactions ?? null)) {
            /** @var list<array<string, mixed>> $rows */
            $rows = array_values(array_filter(array_map(function (mixed $item): array {
                if (is_array($item)) {
                    return $item;
                }

                if (is_object($item)) {
                    return (array) $item;
                }

                return [];
            }, $history->normalized_transactions), fn (array $item) => $item !== []));

            return $rows;
        }

        if (is_object($history) && is_array($history->transactions ?? null)) {
            /** @var list<array<string, mixed>> $rows */
            $rows = array_values(array_filter(array_map(function (mixed $item): array {
                if (is_array($item)) {
                    return $item;
                }

                if (is_object($item)) {
                    return (array) $item;
                }

                return [];
            }, $history->transactions), fn (array $item) => $item !== []));

            return $rows;
        }

        return [];
    }

    protected function cooldownSeconds(): int
    {
        return (int) config('bank-sync.providers.vcb.cooldown_seconds', 10);
    }

    protected function lockSeconds(): int
    {
        return (int) config('bank-sync.providers.vcb.lock_seconds', 20);
    }

    protected function syncLockKey(BankAccount $bankAccount): string
    {
        return "bank-sync:vcb:{$bankAccount->id}";
    }
}
