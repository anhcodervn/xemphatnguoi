<?php

namespace App\Features\Client\Bank\Actions;

use App\Exceptions\ApiException;
use App\Features\Client\Bank\Services\AcbService;
use App\Features\Client\Bank\Services\MbService;
use App\Features\Client\Bank\Services\VcbService;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TransactionBankAction
{
    public function __construct(
        protected AcbService $acbService,
        protected VcbService $vcbService,
        protected MbService $mbService,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function handle(BankAccount $bankAccount, int $limit = 20, bool $forceRefresh = false): array
    {
        return $this->handleWithChanges($bankAccount, $limit, $forceRefresh)['transactions'];
    }

    /**
     * @return array{
     *     transactions: list<array<string, mixed>>,
     *     new_transactions: list<array<string, mixed>>
     * }
     */
    public function handleWithChanges(BankAccount $bankAccount, int $limit = 20, bool $forceRefresh = false): array
    {
        $response = match (strtolower($bankAccount->bank_name)) {
            'acb' => $this->acbService->fetchTransactions($bankAccount, $limit, $forceRefresh),
            'vcb' => $this->vcbService->fetchTransactions($bankAccount, $limit, $forceRefresh),
            'mb' => $this->mbService->fetchTransactions($bankAccount, $limit, $forceRefresh),
            default => throw new ApiException('Ngân hàng này chưa hỗ trợ đồng bộ giao dịch.', 422),
        };

        if (($response['meta']['from_cache'] ?? false) === true) {
            /** @var list<array<string, mixed>> $transactions */
            $transactions = is_array($response['data'] ?? null) ? $response['data'] : [];

            return [
                'transactions' => $transactions,
                'new_transactions' => [],
            ];
        }

        if (($response['status'] ?? null) !== 'success') {
            throw new ApiException(
                (string) ($response['message'] ?? 'Không thể lấy lịch sử giao dịch ngân hàng.'),
                422,
                [
                    'data' => $response['data'] ?? [],
                ],
            );
        }

        $transactions = collect($this->extractTransactionRows($response['data'] ?? []))
            ->map(fn (array $transactionRow) => $this->normalizeTransaction($bankAccount, $transactionRow))
            ->filter(fn (array $transaction) => $transaction['transaction_id'] !== '')
            ->values();

        $newTransactions = [];

        $transactions->each(function (array $transaction) use ($bankAccount, &$newTransactions): void {
            $model = BankTransaction::query()->updateOrCreate(
                [
                    'bank_account_id' => $bankAccount->id,
                    'transaction_id' => $transaction['transaction_id'],
                ],
                [
                    'amount' => $transaction['amount'],
                    'description' => $transaction['description'],
                    'transaction_time' => $transaction['transaction_time'],
                    'type' => $transaction['type'],
                    'raw_data' => $transaction['raw_data'],
                ],
            );

            if ($model->wasRecentlyCreated) {
                $newTransactions[] = $this->serializeTransaction($model);
            }
        });

        $bankAccount->forceFill([
            'last_sync_at' => now(),
            'status' => 'active',
        ])->save();

        $serializedTransactions = BankTransaction::query()
            ->whereBelongsTo($bankAccount)
            ->orderByDesc('transaction_time')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (BankTransaction $transaction) => $this->serializeTransaction($transaction))
            ->all();

        return [
            'transactions' => $serializedTransactions,
            'new_transactions' => $newTransactions,
        ];
    }

    /**
     * @param  mixed  $payload
     * @return list<array<string, mixed>>
     */
    protected function extractTransactionRows(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if ($this->isTransactionList($payload)) {
            return $payload;
        }

        $candidateKeys = [
            'transactions',
            'transactionList',
            'transaction_list',
            'items',
            'data',
            'result',
            'list',
            'history',
            'txHistory',
        ];

        foreach ($candidateKeys as $key) {
            $candidate = data_get($payload, $key);

            if (is_array($candidate) && $this->isTransactionList($candidate)) {
                return $candidate;
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $transactions = $this->extractTransactionRows($value);

                if ($transactions !== []) {
                    return $transactions;
                }
            }
        }

        return [];
    }

    /**
     * @param  array<int, mixed>  $items
     */
    protected function isTransactionList(array $items): bool
    {
        return $items !== [] && array_is_list($items) && is_array($items[0] ?? null);
    }

    /**
     * @param  array<string, mixed>  $transactionRow
     * @return array{
     *     transaction_id: string,
     *     amount: float,
     *     description: ?string,
     *     transaction_time: ?string,
     *     type: ?string,
     *     raw_data: array<string, mixed>
     * }
     */
    protected function normalizeTransaction(BankAccount $bankAccount, array $transactionRow): array
    {
        $description = $this->resolveFirstString($transactionRow, [
            'description',
            'remark',
            'remarks',
            'narrative',
            'content',
            'details',
            'descriptionText',
            'transactionDescription',
        ]);

        $amount = $this->resolveAmount($transactionRow);
        $type = $this->resolveType($transactionRow, $amount);
        $transactionTime = $this->resolveTransactionTime($transactionRow);
        $transactionId = $this->resolveAcbTransactionId($bankAccount, $transactionRow) ?? $this->resolveFirstString($transactionRow, [
            'transactionId',
            'transaction_id',
            'txnId',
            'txn_id',
            'reference',
            'referenceNumber',
            'referenceNo',
            'refNo',
            'traceId',
            'id',
        ]);

        if ($transactionId === null || $transactionId === '') {
            $transactionId = hash('sha256', implode('|', [
                $bankAccount->id,
                $transactionTime ?? '',
                number_format($amount, 2, '.', ''),
                $description ?? '',
                json_encode($transactionRow),
            ]));
        }

        return [
            'transaction_id' => $transactionId,
            'amount' => abs($amount),
            'description' => $description,
            'transaction_time' => $transactionTime,
            'type' => $type,
            'raw_data' => $transactionRow,
        ];
    }

    /**
     * @param  array<string, mixed>  $transactionRow
     */
    protected function resolveAmount(array $transactionRow): float
    {
        $candidateFields = [
            'amount',
            'transactionAmount',
            'transaction_amount',
            'value',
            'creditAmount',
            'credit_amount',
            'debitAmount',
            'debit_amount',
        ];

        foreach ($candidateFields as $field) {
            $value = data_get($transactionRow, $field);

            if ($value === null || $value === '') {
                continue;
            }

            return $this->toFloat($value);
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $transactionRow
     */
    protected function resolveType(array $transactionRow, float $amount): ?string
    {
        $type = $this->resolveFirstString($transactionRow, [
            'type',
            'transactionType',
            'transaction_type',
            'drCr',
            'debitCredit',
        ]);

        if ($type !== null) {
            $normalizedType = strtolower($type);

            return match (true) {
                in_array($normalizedType, ['credit', 'cr', 'in', 'incoming'], true) => 'credit',
                in_array($normalizedType, ['debit', 'dr', 'out', 'outgoing'], true) => 'debit',
                default => null,
            };
        }

        if (data_get($transactionRow, 'creditAmount') !== null || data_get($transactionRow, 'credit_amount') !== null) {
            return 'credit';
        }

        if (data_get($transactionRow, 'debitAmount') !== null || data_get($transactionRow, 'debit_amount') !== null) {
            return 'debit';
        }

        if ($amount < 0) {
            return 'debit';
        }

        if ($amount > 0) {
            return 'credit';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $transactionRow
     */
    protected function resolveAcbTransactionId(BankAccount $bankAccount, array $transactionRow): ?string
    {
        if (strtolower($bankAccount->bank_name) !== 'acb') {
            return null;
        }

        $transactionNumber = data_get($transactionRow, 'transactionNumber');
        $activeDatetime = data_get($transactionRow, 'activeDatetime');

        if ($transactionNumber === null || $activeDatetime === null) {
            return null;
        }

        $transactionNumberValue = is_scalar($transactionNumber) ? trim((string) $transactionNumber) : '';
        $activeDatetimeValue = is_scalar($activeDatetime) ? trim((string) $activeDatetime) : '';

        if ($transactionNumberValue === '' || $activeDatetimeValue === '') {
            return null;
        }

        return "{$transactionNumberValue}_{$activeDatetimeValue}";
    }

    /**
     * @param  array<string, mixed>  $transactionRow
     */
    protected function resolveTransactionTime(array $transactionRow): ?string
    {
        $candidateFields = [
            'activeDatetime',
            'active_datetime',
            'transactionTime',
            'transaction_time',
            'effectiveDate',
            'effective_date',
            'postingDate',
            'posting_date',
            'transactionDate',
            'transaction_date',
            'bookingDate',
            'booking_date',
            'date',
            'time',
        ];

        foreach ($candidateFields as $field) {
            $value = data_get($transactionRow, $field);

            if ($value === null || $value === '') {
                continue;
            }

            $resolvedDateTime = $this->parseDateTimeValue($value);

            if ($resolvedDateTime !== null) {
                return $resolvedDateTime;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $transactionRow
     * @param  list<string>  $fields
     */
    protected function resolveFirstString(array $transactionRow, array $fields): ?string
    {
        foreach ($fields as $field) {
            $value = data_get($transactionRow, $field);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    protected function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return 0.0;
        }

        $normalizedValue = str_replace([',', ' '], '', $value);

        return is_numeric($normalizedValue) ? (float) $normalizedValue : 0.0;
    }

    protected function parseDateTimeValue(mixed $value): ?string
    {
        try {
            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                $numericValue = (float) $value;

                if ($numericValue > 9999999999) {
                    return Carbon::createFromTimestampMs((int) round($numericValue))->toDateTimeString();
                }

                if ($numericValue > 0) {
                    return Carbon::createFromTimestamp((int) round($numericValue))->toDateTimeString();
                }
            }

            if (is_string($value) && trim($value) !== '') {
                return Carbon::parse($value)->toDateTimeString();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeTransaction(BankTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->transaction_id,
            'amount' => (string) $transaction->amount,
            'description' => $transaction->description,
            'transaction_time' => $this->serializeTransactionTime($transaction->transaction_time),
            'type' => $transaction->type,
            'raw_data' => $transaction->raw_data,
            'created_at' => $this->serializeTransactionTime($transaction->created_at),
            'updated_at' => $this->serializeTransactionTime($transaction->updated_at),
        ];
    }

    protected function serializeTransactionTime(CarbonInterface|string|null $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        return $value;
    }
}
