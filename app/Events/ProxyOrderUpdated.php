<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProxyOrderUpdated implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $orderId,
        public readonly ?int $targetProxyId,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $errorMessage = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.{$this->userId}.proxy-orders");
    }

    public function broadcastAs(): string
    {
        return 'proxy.order.updated';
    }

    /** @return array{order_id: int, target_proxy_id: ?int, type: string, status: string, error_message: ?string} */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'target_proxy_id' => $this->targetProxyId,
            'type' => $this->type,
            'status' => $this->status,
            'error_message' => $this->errorMessage,
        ];
    }
}
