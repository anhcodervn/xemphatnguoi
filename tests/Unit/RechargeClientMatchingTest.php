<?php

use App\Features\Api\V1\Actions\MatchRechargeClientOrdersAction;
use App\Models\RechargeClient;

test('recharge client matcher only matches credit transaction with same amount and transfer content', function () {
    $order = new RechargeClient([
        'amount' => '150000.00',
        'transfer_content' => 'NAPABCD1234',
    ]);

    $action = new MatchRechargeClientOrdersAction();

    expect($action->transactionMatches($order, [
        'type' => 'credit',
        'amount' => '150000',
        'description' => 'Khach chuyen tien NAPABCD1234 vao tai khoan',
    ]))->toBeTrue();

    expect($action->transactionMatches($order, [
        'type' => 'debit',
        'amount' => '150000',
        'description' => 'Khach chuyen tien NAPABCD1234 vao tai khoan',
    ]))->toBeFalse();

    expect($action->transactionMatches($order, [
        'type' => 'credit',
        'amount' => '149999',
        'description' => 'Khach chuyen tien NAPABCD1234 vao tai khoan',
    ]))->toBeFalse();

    expect($action->transactionMatches($order, [
        'type' => 'credit',
        'amount' => '150000',
        'description' => 'Noi dung khong khop',
    ]))->toBeFalse();
});
