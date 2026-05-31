<?php

use App\Jobs\SyncBankAccountTransactionsJob;

test('webhook events empty array dispatches all credit transactions', function () {
    $job = new class(1) extends SyncBankAccountTransactionsJob
    {
        public function exposesShouldDispatchAllTransactions(mixed $eventKeyword): bool
        {
            return $this->shouldDispatchAllTransactions($eventKeyword);
        }
    };

    expect($job->exposesShouldDispatchAllTransactions(''))->toBeTrue()
        ->and($job->exposesShouldDispatchAllTransactions(null))->toBeTrue();
});

test('webhook events containing empty string dispatches all credit transactions', function () {
    $job = new class(1) extends SyncBankAccountTransactionsJob
    {
        public function exposesShouldDispatchAllTransactions(mixed $eventKeyword): bool
        {
            return $this->shouldDispatchAllTransactions($eventKeyword);
        }
    };

    expect($job->exposesShouldDispatchAllTransactions(''))->toBeTrue()
        ->and($job->exposesShouldDispatchAllTransactions('   '))->toBeTrue();
});

test('webhook events with only real keywords require keyword matching', function () {
    $job = new class(1) extends SyncBankAccountTransactionsJob
    {
        public function exposesShouldDispatchAllTransactions(mixed $eventKeyword): bool
        {
            return $this->shouldDispatchAllTransactions($eventKeyword);
        }
    };

    expect($job->exposesShouldDispatchAllTransactions('nap'))->toBeFalse();
});
