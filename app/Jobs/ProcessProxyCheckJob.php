<?php

namespace App\Jobs;

use App\Features\Client\Proxy\Services\ProxyCheckerService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessProxyCheckJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 15;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 300;

    public function __construct(public readonly int $proxyCheckItemId)
    {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return (string) $this->proxyCheckItemId;
    }

    public function handle(ProxyCheckerService $proxyCheckerService): void
    {
        $proxyCheckerService->process($this->proxyCheckItemId);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception instanceof Throwable) {
            report($exception);
        }

        app(ProxyCheckerService::class)->fail($this->proxyCheckItemId);
    }
}
