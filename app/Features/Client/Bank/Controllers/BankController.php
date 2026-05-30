<?php

namespace App\Features\Client\Bank\Controllers;

use App\Features\Client\Bank\Actions\SaveBankAction;
use App\Features\Client\Bank\Actions\TransactionBankAction;
use App\Features\Client\Bank\Requests\SaveBankRequest;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Utils\EncodeBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index(): JsonResponse
    {
        $banks = Bank::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'short_name',
                'logo',
                'bg_color',
                'metadata',
            ]);

        return response()->json([
            'status' => true,
            'data' => $banks,
        ]);
    }

    public function accounts(): JsonResponse
    {
        $accounts = BankAccount::query()
            ->leftJoin('banks', 'banks.code', '=', 'bank_accounts.bank_name')
            ->orderByDesc('bank_accounts.updated_at')
            ->orderByDesc('bank_accounts.id')
            ->select([
                'bank_accounts.id',
                'bank_accounts.bank_name as bank_code',
                'bank_accounts.account_name',
                'bank_accounts.account_number',
                'bank_accounts.username',
                'bank_accounts.status',
                'bank_accounts.last_sync_at',
                'bank_accounts.created_at',
                'bank_accounts.updated_at',
                'banks.name as bank_full_name',
                'banks.short_name as bank_short_name',
                'banks.logo as bank_logo',
                'banks.bg_color as bank_bg_color',
            ])
            ->get()
            ->map(fn ($account) => [
                'id' => $account->id,
                'bank_code' => $account->bank_code,
                'bank_name' => $account->bank_short_name ?: $account->bank_full_name ?: strtoupper((string) $account->bank_code),
                'bank_full_name' => $account->bank_full_name,
                'bank_short_name' => $account->bank_short_name,
                'bank_logo' => $account->bank_logo,
                'bank_bg_color' => $account->bank_bg_color ?: '#2563EB',
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'username' => EncodeBank::decode($account->username),
                'status' => $account->status,
                'last_sync_at' => $account->last_sync_at,
                'created_at' => $account->created_at,
                'updated_at' => $account->updated_at,
            ]);

        return response()->json([
            'status' => true,
            'data' => $accounts,
        ]);
    }

    public function showAccount(BankAccount $bankAccount): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->serializeBankAccountWithBankMeta($bankAccount),
        ]);
    }

    public function transactions(Request $request, 
    BankAccount $bankAccount, 
    TransactionBankAction $action): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'force_refresh' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Lấy lịch sử giao dịch thành công.',
            'data' => $action->handle(
                $bankAccount,
                $validated['limit'] ?? 20,
                $request->boolean('force_refresh'),
            ),
        ]);
    }

    public function saveBank(SaveBankRequest $request, SaveBankAction $action): JsonResponse
    {
        $bankAccount = $action->handle($request->validated());

        return response()->json([
            'status' => true,
            'message' => $bankAccount->wasRecentlyCreated
                ? 'Liên kết tài khoản ngân hàng thành công.'
                : 'Cập nhật tài khoản ngân hàng thành công.',
            'data' => $this->serializeBankAccount($bankAccount),
        ]);
    }

    public function updateBank(BankAccount $bankAccount, SaveBankRequest $request, SaveBankAction $action): JsonResponse
    {
        $updatedBankAccount = $action->handle($request->validated(), $bankAccount);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật tài khoản ngân hàng thành công.',
            'data' => $this->serializeBankAccount($updatedBankAccount),
        ]);
    }

    public function destroyAccount(BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa liên kết thẻ thành công.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeBankAccount(BankAccount $bankAccount): array
    {
        return [
            'id' => $bankAccount->id,
            'bank_code' => $bankAccount->bank_name,
            'bank_name' => $bankAccount->bank_name,
            'account_name' => $bankAccount->account_name,
            'account_number' => $bankAccount->account_number,
            'username' => $bankAccount->username,
            'status' => $bankAccount->status,
            'last_sync_at' => $bankAccount->last_sync_at,
            'created_at' => $bankAccount->created_at,
            'updated_at' => $bankAccount->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeBankAccountWithBankMeta(BankAccount $bankAccount): array
    {
        $bank = Bank::query()
            ->where('code', $bankAccount->bank_name)
            ->first([
                'id',
                'code',
                'name',
                'short_name',
                'logo',
                'bg_color',
            ]);

        return [
            ...$this->serializeBankAccount($bankAccount),
            'bank_full_name' => $bank?->name,
            'bank_short_name' => $bank?->short_name,
            'bank_logo' => $bank?->logo,
            'bank_bg_color' => $bank?->bg_color ?: '#2563EB',
        ];
    }
}
