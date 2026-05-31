<?php

namespace App\Jobs;

use App\Models\UserLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SaveUserLogJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        public int $userId,
        public string $action,
        public ?string $description = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {}

    public function handle(): void
    {
        UserLog::query()->create([
            'user_id' => $this->userId,
            'action' => $this->action,
            'description' => $this->description,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
        ]);
    }
}
