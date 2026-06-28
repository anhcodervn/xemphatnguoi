<?php

namespace App\Features\Recharge\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiBankVnCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_keyword' => ['nullable', 'string', 'max:120'],
            'webhook_id' => ['nullable', 'integer'],
            'bank_id' => ['required', 'integer'],
            'bank_account_id' => ['nullable', 'integer'],
            'sign' => ['required', 'string', 'max:255'],
            'order_code' => ['nullable', 'string', 'max:120'],
            'client_order_code' => ['nullable', 'string', 'max:120'],
            'transfer_content' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'transaction_description' => ['nullable', 'string', 'max:2000'],
            'transaction_time' => ['nullable', 'string', 'max:120'],
            'transaction_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:120'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'string', 'max:120'],
            'requested_at' => ['nullable', 'string', 'max:120'],
            'expires_at' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $order = $this->input('data.order');
        $transaction = $this->input('payload.transaction');
        $transactionRawData = is_array($transaction) ? ($transaction['raw_data'] ?? null) : null;
        $deepRawData = is_array($transactionRawData) ? ($transactionRawData['raw_data'] ?? null) : null;

        if (! is_array($order)) {
            $fallback = $this->input('order');
            $order = is_array($fallback) ? $fallback : [];
        }

        if (! is_array($transaction)) {
            $transaction = [];
        }

        $transactionType = (string) ($this->input('transaction_type', $transaction['type'] ?? ''));
        $resolvedStatus = $this->input('status', $order['status'] ?? null);

        if ($resolvedStatus === null && $transactionType !== '') {
            $resolvedStatus = strcasecmp($transactionType, 'credit') === 0 ? 'paid' : 'pending';
        }

        $this->merge([
            'event_keyword' => $this->input('event_keyword'),
            'webhook_id' => $this->input('webhook_id'),
            'bank_id' => $this->input('bank_id'),
            'bank_account_id' => $this->input('bank_account_id'),
            'sign' => $this->input('sign'),
            'order_code' => $this->input('order_code', $order['order_code'] ?? null),
            'client_order_code' => $this->input('client_order_code', $order['client_order_code'] ?? null),
            'transfer_content' => $this->input('transfer_content', $order['transfer_content'] ?? null),
            'transaction_id' => $this->input('transaction_id', $transaction['transaction_id'] ?? $transaction['id'] ?? null),
            'transaction_description' => $this->input('transaction_description', $transaction['description'] ?? null),
            'transaction_time' => $this->input('transaction_time', $transaction['transaction_time'] ?? null),
            'transaction_type' => $transactionType !== '' ? $transactionType : null,
            'status' => $resolvedStatus,
            'amount' => $this->input('amount', $order['amount'] ?? $transaction['amount'] ?? null),
            'bank_name' => $this->input('bank_name', $order['bank_name'] ?? null),
            'account_number' => $this->input(
                'account_number',
                $order['account_number']
                    ?? (is_array($deepRawData) ? ($deepRawData['accountNo'] ?? null) : null)
                    ?? (is_array($transactionRawData) ? ($transactionRawData['accountNo'] ?? null) : null)
            ),
            'account_name' => $this->input('account_name', $order['account_name'] ?? null),
            'paid_at' => $this->input('paid_at', $order['paid_at'] ?? $transaction['transaction_time'] ?? null),
            'requested_at' => $this->input('requested_at', $order['requested_at'] ?? null),
            'expires_at' => $this->input('expires_at', $order['expires_at'] ?? null),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function callbackPayload(): array
    {
        return $this->validated();
    }

    public function webhookSecret(): string
    {
        return trim((string) $this->header('X-Webhook-Secret', ''));
    }
}
