<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletBalanceChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param array{id:int,scope:string,title:string,content:string,redirect_url:?string,type:?string,is_read:bool,created_at:?string}|null $notification */
    public function __construct(
        public readonly int $userId,
        public readonly string $walletType,
        public readonly string $balance,
        public readonly string $holdBalance,
        public readonly string $totalRecharge,
        public readonly string $totalSpent,
        public readonly string $changeType,
        public readonly string $amount,
        public readonly int $transactionId,
        public readonly string $description,
        public readonly string $changedAt,
        public readonly ?array $notification = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.{$this->userId}.wallet");
    }

    public function broadcastAs(): string
    {
        return 'wallet.balance.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'wallet_type' => $this->walletType,
            'balance' => $this->balance,
            'hold_balance' => $this->holdBalance,
            'total_recharge' => $this->totalRecharge,
            'total_spent' => $this->totalSpent,
            'change_type' => $this->changeType,
            'amount' => $this->amount,
            'transaction_id' => $this->transactionId,
            'description' => $this->description,
            'changed_at' => $this->changedAt,
            'notification' => $this->notification,
        ];
    }
}
