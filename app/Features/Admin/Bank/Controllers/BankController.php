<?php

namespace App\Features\Admin\Bank\Controllers;

use App\Features\Admin\Bank\Requests\StoreBankRequest;
use App\Features\Admin\Bank\Requests\UpdateBankRequest;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $query = Bank::query()
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($bankQuery) use ($search): void {
                    $bankQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('short_name', 'like', "%{$search}%");
                });
            });

        $banks = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => true,
            'data' => [
                'banks' => $banks,
                'summary' => [
                    'total' => (clone $query)->count(),
                    'active' => (clone $query)->where('is_active', true)->count(),
                    'inactive' => (clone $query)->where('is_active', false)->count(),
                ],
            ],
        ]);
    }

    public function show(Bank $bank): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $bank,
        ]);
    }

    public function store(StoreBankRequest $request): JsonResponse
    {
        $bank = Bank::query()->create([
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'short_name' => $request->validated('short_name'),
            'logo' => $request->validated('logo'),
            'bg_color' => $request->validated('bg_color', '#FFFFFF'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->integer('sort_order', 0),
            'limit_request_per_minute' => $request->integer('limit_request_per_minute', 6),
            'metadata' => $request->validated('metadata', []),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tạo ngân hàng thành công.',
            'data' => $bank,
        ], 201);
    }

    public function update(UpdateBankRequest $request, Bank $bank): JsonResponse
    {
        $validated = $request->validated();

        $bank->fill([
            'code' => $validated['code'] ?? $bank->code,
            'name' => $validated['name'] ?? $bank->name,
            'short_name' => $validated['short_name'] ?? $bank->short_name,
            'logo' => $validated['logo'] ?? $bank->logo,
            'bg_color' => $validated['bg_color'] ?? $bank->bg_color,
            'is_active' => $validated['is_active'] ?? $bank->is_active,
            'sort_order' => $validated['sort_order'] ?? $bank->sort_order,
            'limit_request_per_minute' => $validated['limit_request_per_minute'] ?? $bank->limit_request_per_minute,
            'metadata' => $validated['metadata'] ?? $bank->metadata,
        ]);

        $bank->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật ngân hàng thành công.',
            'data' => $bank->fresh(),
        ]);
    }

    public function destroy(Bank $bank): JsonResponse
    {
        $deletedBankAccounts = 0;

        DB::transaction(function () use ($bank, &$deletedBankAccounts): void {
            $deletedBankAccounts = BankAccount::query()
                ->where('bank_name', $bank->code)
                ->delete();

            $bank->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Xóa ngân hàng thành công.',
            'data' => [
                'deleted_bank_accounts' => $deletedBankAccounts,
            ],
        ]);
    }
}
