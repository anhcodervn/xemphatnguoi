<?php

namespace App\Features\Client\Bank\Actions;

use App\Features\Client\Bank\Services\AcbService;
use App\Features\Client\Bank\Services\MbService;
use App\Features\Client\Bank\Services\VcbService;
use App\Models\Bank;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

class SaveBankAction
{
    public function __construct(
        protected AcbService $acbService,
        protected VcbService $vcbService,
        protected MbService $mbService,
    ) {
    }

    /**
     * @param  array{
     *     bank_code: string,
     *     display_name: string,
     *     username: string,
     *     password?: ?string,
     *     account_number: string
     * }  $payload
     */
    public function handle(array $payload, ?BankAccount $bankAccount = null): BankAccount
    {
        $bank = Bank::query()
            ->where('code', $payload['bank_code'])
            ->where('is_active', true)
            ->firstOrFail([
                'code',
            ]);

        if ($bank->code === 'acb') {
            return $this->acbService->saveBank($payload, $bankAccount);
        } elseif ($bank->code === 'vcb') {
            return $this->vcbService->saveBank($payload, $bankAccount);
        } elseif ($bank->code === 'mb') {
            return $this->mbService->saveBank($payload, $bankAccount);
        } else {
            return DB::transaction(function () use ($bank, $payload, $bankAccount): BankAccount {
                $targetBankAccount = $bankAccount
                    ?? BankAccount::query()->firstOrNew([
                        'bank_name' => $bank->code,
                        'account_number' => $payload['account_number'],
                    ]);

                $targetBankAccount->forceFill([
                    'bank_name' => $bank->code,
                    'account_name' => $payload['display_name'],
                    'account_number' => $payload['account_number'],
                    'username' => $payload['username'],
                    'status' => 'active',
                ]);

                if (array_key_exists('password', $payload) && filled($payload['password'])) {
                    $targetBankAccount->password = $payload['password'];
                }

                $targetBankAccount->save();

                return $targetBankAccount->fresh() ?? $targetBankAccount;
            });
        }
    }
}
