<?php

namespace App\Features\Client\Wallet\Actions;

use App\Models\WalletTransaction;
use App\Support\MailQueue;

class DispatchWalletBalanceChangedMailAction
{
    public function __construct(
        private readonly MailQueue $mailQueue,
    ) {}

    public function handle(WalletTransaction $walletTransaction): void
    {
        if ($walletTransaction->reference_type === 'traffic_fine_api_request') {
            return;
        }

        $walletTransaction->loadMissing('wallet.user');

        $user = $walletTransaction->wallet?->user;
        $userEmail = is_string($user?->email) ? trim($user->email) : '';

        if ($userEmail === '' || $walletTransaction->status !== 'success') {
            return;
        }

        $amount = number_format((float) $walletTransaction->amount, 0, ',', '.').'đ';
        $balanceBefore = number_format((float) $walletTransaction->balance_before, 0, ',', '.').'đ';
        $balanceAfter = number_format((float) $walletTransaction->balance_after, 0, ',', '.').'đ';
        $isCredit = $walletTransaction->type === 'credit';

        $subjectText = $isCredit ? 'Số dư ví vừa tăng' : 'Số dư ví vừa giảm';
        $title = $isCredit ? 'Biến động tăng số dư ví' : 'Biến động giảm số dư ví';

        $messageLines = [
            sprintf('Số tiền biến động: %s', $amount),
            sprintf('Số dư trước biến động: %s', $balanceBefore),
            sprintf('Số dư sau biến động: %s', $balanceAfter),
        ];

        if (is_string($walletTransaction->description) && trim($walletTransaction->description) !== '') {
            $messageLines[] = sprintf('Nội dung: %s', trim($walletTransaction->description));
        }

        $this->mailQueue->dispatch(
            to: $userEmail,
            subjectText: $subjectText,
            title: $title,
            messageLines: $messageLines,
        );
    }
}
