<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletDepositCredited implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array{id: int, scope: string, title: string, content: string, redirect_url: string, type: string, is_read: bool, created_at: ?string}  $notification
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $paymentTransactionId,
        public readonly string $transactionCode,
        public readonly string $amount,
        public readonly string $balance,
        public readonly string $totalRecharge,
        public readonly string $creditedAt,
        public readonly array $notification,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.{$this->userId}.wallet");
    }

    public function broadcastAs(): string
    {
        return 'wallet.deposit.credited';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'payment_transaction_id' => $this->paymentTransactionId,
            'transaction_code' => $this->transactionCode,
            'amount' => $this->amount,
            'balance' => $this->balance,
            'total_recharge' => $this->totalRecharge,
            'status' => 'paid',
            'message' => 'Nạp tiền thành công.',
            'credited_at' => $this->creditedAt,
            'notification' => $this->notification,
        ];
    }
}
