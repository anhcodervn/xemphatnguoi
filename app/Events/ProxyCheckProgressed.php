<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProxyCheckProgressed implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param array<string, mixed> $item */
    public function __construct(
        public readonly int $userId,
        public readonly string $batchId,
        public readonly string $batchStatus,
        public readonly int $total,
        public readonly int $processed,
        public readonly int $live,
        public readonly int $die,
        public readonly array $item,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.{$this->userId}.proxy-checks");
    }

    public function broadcastAs(): string
    {
        return 'proxy.check.progressed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'batch_id' => $this->batchId,
            'status' => $this->batchStatus,
            'total' => $this->total,
            'processed' => $this->processed,
            'live' => $this->live,
            'die' => $this->die,
            'progress' => $this->total > 0 ? (int) floor(($this->processed / $this->total) * 100) : 0,
            'item' => $this->item,
        ];
    }
}
