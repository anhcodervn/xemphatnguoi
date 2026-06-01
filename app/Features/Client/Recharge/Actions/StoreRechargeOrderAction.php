<?php

namespace App\Features\Client\Recharge\Actions;

use App\Models\RechargeOrder;
use App\Models\User;
use App\Support\RechargeMethodCatalog;
use Illuminate\Support\Str;

class StoreRechargeOrderAction
{
    private const DEFAULT_QR_TEMPLATE = 'https://img.vietqr.io/image/{METHOD_CODE_UPPER}-{ACCOUNT_NUMBER}-compact.png?addInfo={ORDER_CODE}&amount={AMOUNT}';

    public function __construct(
        protected RechargeMethodCatalog $rechargeMethodCatalog,
    ) {}

    /**
     * @param  array{method:string,amount:int|float}  $payload
     */
    public function handle(User $user, array $payload): RechargeOrder
    {
        $methodConfig = $this->rechargeMethodCatalog->find($payload['method']);

        if ($methodConfig === null) {
            abort(422, 'Phương thức nạp không khả dụng.');
        }

        $amount = round((float) $payload['amount'], 2);
        $bonusAmount = round($amount * ((int) ($methodConfig['bonus_percentage'] ?? 0) / 100), 2);
        $orderCode = $this->generateOrderCode();

        return $user->rechargeOrders()->create([
            'recharge_method_id' => $methodConfig['recharge_method_id'] ?? null,
            'bank_account_id' => $methodConfig['bank_account_id'] ?? null,
            'order_code' => $orderCode,
            'method' => $payload['method'],
            'method_label' => (string) $methodConfig['label'],
            'amount' => $amount,
            'bonus_amount' => $bonusAmount,
            'total_amount' => $amount + $bonusAmount,
            'bank_name' => $methodConfig['bank_name'] ?? null,
            'account_number' => $methodConfig['account_number'] ?? null,
            'account_name' => $methodConfig['account_name'] ?? null,
            'transfer_content' => $orderCode,
            'status' => RechargeOrder::STATUS_PENDING,
            'requested_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'metadata' => $this->buildMetadata($methodConfig, $orderCode, $amount),
        ]);
    }

    protected function generateOrderCode(): string
    {
        return 'DEP'.now()->format('ymdHis').Str::upper(Str::random(4));
    }

    /**
     * @param  array<string, mixed>  $methodConfig
     * @return array<string, mixed>
     */
    private function buildMetadata(array $methodConfig, string $orderCode, float $amount): array
    {
        /** @var array<string, mixed> $metadata */
        $metadata = is_array($methodConfig['metadata'] ?? null) ? $methodConfig['metadata'] : [];

        $metadata = [
            ...$metadata,
            'description' => $methodConfig['description'] ?? null,
            'badge_label' => $methodConfig['badge_label'] ?? null,
            'badge_type' => $methodConfig['badge_type'] ?? null,
            'source' => $methodConfig['source'] ?? null,
        ];

        $qrUrl = $this->resolveQrUrl($methodConfig, $metadata, $orderCode, $amount);

        if ($qrUrl !== null) {
            $metadata['qr_url'] = $qrUrl;
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $methodConfig
     * @param  array<string, mixed>  $metadata
     */
    private function resolveQrUrl(array $methodConfig, array $metadata, string $orderCode, float $amount): ?string
    {
        $accountNumber = trim((string) ($methodConfig['account_number'] ?? ''));
        $methodCode = trim((string) ($methodConfig['key'] ?? $methodConfig['code'] ?? ''));

        if ($accountNumber === '' || $methodCode === '') {
            return null;
        }

        $template = trim((string) ($metadata['qr_template'] ?? self::DEFAULT_QR_TEMPLATE));

        if ($template === '') {
            return null;
        }

        return strtr($template, [
            '{METHOD_CODE}' => Str::lower($methodCode),
            '{METHOD_CODE_UPPER}' => Str::upper($methodCode),
            '{ACCOUNT_NUMBER}' => $accountNumber,
            '{ORDER_CODE}' => rawurlencode($orderCode),
            '{TRANSFER_CONTENT}' => rawurlencode($orderCode),
            '{AMOUNT}' => $this->formatAmount($amount),
        ]);
    }

    private function formatAmount(float $amount): string
    {
        if ((float) (int) $amount === $amount) {
            return (string) (int) $amount;
        }

        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }
}
