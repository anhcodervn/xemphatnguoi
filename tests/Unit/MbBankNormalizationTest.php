<?php

use App\Service\ApiBank\MbBank;
use Tests\TestCase;

uses(TestCase::class);

test('mb bank normalizes inbound transaction using credit and debit delta', function () {
    $service = new class('demo-user', 'demo-pass') extends MbBank {
        public function exposeNormalizeTransaction(array $row): array
        {
            return $this->normalizeTransaction($row);
        }
    };

    $transaction = $service->exposeNormalizeTransaction([
        'postingDate' => '25/05/2026 00:01:00',
        'transactionDate' => '24/05/2026 20:15:01',
        'accountNo' => '036344982444',
        'creditAmount' => '625000',
        'debitAmount' => '0',
        'currency' => 'VND',
        'description' => 'TRAN KHANH LY 13972577 240526 20 15 00 6144ASCB02 1URKVK 021URKVK/017475',
        'refNo' => 'FT26145295030431',
        'transactionType' => 'BI2B',
    ]);

    expect($transaction['transaction_id'])->toBe('FT26145295030431')
        ->and($transaction['amount'])->toBe(625000.0)
        ->and($transaction['type'])->toBe('credit')
        ->and($transaction['transaction_time'])->toBe('2026-05-24 20:15:01');
});

test('mb bank normalizes outbound transaction using credit and debit delta', function () {
    $service = new class('demo-user', 'demo-pass') extends MbBank {
        public function exposeNormalizeTransaction(array $row): array
        {
            return $this->normalizeTransaction($row);
        }
    };

    $transaction = $service->exposeNormalizeTransaction([
        'postingDate' => '28/05/2026 08:46:32',
        'creditAmount' => '0',
        'debitAmount' => '100000',
        'description' => 'MBVCB transfer out',
        'refNo' => 'FT26145295039999',
    ]);

    expect($transaction['amount'])->toBe(100000.0)
        ->and($transaction['type'])->toBe('debit')
        ->and($transaction['transaction_time'])->toBe('2026-05-28 08:46:32');
});
