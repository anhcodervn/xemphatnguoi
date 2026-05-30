<?php

namespace App\Features\Admin\Recharge\Controllers;

use App\Features\Admin\Recharge\Requests\StoreRechargeMethodRequest;
use App\Features\Admin\Recharge\Requests\UpdateRechargeMethodRequest;
use App\Http\Controllers\Controller;
use App\Models\RechargeMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RechargeMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());
        $status = $request->input('status');
        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        $methods = RechargeMethod::query()
            ->with(['bankAccounts:id,bank_name,account_name,account_number,status'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $methodQuery) use ($search): void {
                    $methodQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%")
                        ->orWhereHas('bankAccounts', function (Builder $bankAccountQuery) use ($search): void {
                            $bankAccountQuery
                                ->where('bank_name', 'like', "%{$search}%")
                                ->orWhere('account_number', 'like', "%{$search}%")
                                ->orWhere('account_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== null && $status !== '', function (Builder $query) use ($status): void {
                $parsedStatus = filter_var($status, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

                if ($parsedStatus !== null) {
                    $query->where('is_active', $parsedStatus);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => true,
            'data' => [
                'methods' => $methods,
                'summary' => [
                    'total' => RechargeMethod::query()->count(),
                    'active' => RechargeMethod::query()->where('is_active', true)->count(),
                    'inactive' => RechargeMethod::query()->where('is_active', false)->count(),
                ],
            ],
        ]);
    }

    public function show(RechargeMethod $rechargeMethod): JsonResponse
    {
        $rechargeMethod->load(['bankAccounts:id,bank_name,account_name,account_number,status']);

        return response()->json([
            'status' => true,
            'data' => $rechargeMethod,
        ]);
    }

    public function store(StoreRechargeMethodRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $rechargeMethod = RechargeMethod::query()->create($this->payload($validated));
        $this->syncBankAccounts($rechargeMethod, $validated['bank_account_ids'] ?? []);

        return response()->json([
            'status' => true,
            'message' => 'Tạo phương thức nạp thành công.',
            'data' => $rechargeMethod->load(['bankAccounts:id,bank_name,account_name,account_number,status']),
        ], 201);
    }

    public function update(UpdateRechargeMethodRequest $request, RechargeMethod $rechargeMethod): JsonResponse
    {
        $validated = $request->validated();
        $rechargeMethod->update($this->payload($validated));
        $this->syncBankAccounts($rechargeMethod, $validated['bank_account_ids'] ?? []);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật phương thức nạp thành công.',
            'data' => $rechargeMethod->fresh()->load(['bankAccounts:id,bank_name,account_name,account_number,status']),
        ]);
    }

    public function destroy(RechargeMethod $rechargeMethod): JsonResponse
    {
        $rechargeMethod->bankAccounts()->detach();
        $rechargeMethod->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa phương thức nạp thành công.',
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        return [
            'code' => trim((string) $validated['code']),
            'name' => trim((string) $validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'badge_label' => filled($validated['badge_label'] ?? null) ? trim((string) $validated['badge_label']) : null,
            'badge_type' => $validated['badge_type'],
            'bank_name' => filled($validated['bank_name'] ?? null) ? trim((string) $validated['bank_name']) : null,
            'account_number' => filled($validated['account_number'] ?? null) ? trim((string) $validated['account_number']) : null,
            'account_name' => filled($validated['account_name'] ?? null) ? trim((string) $validated['account_name']) : null,
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'],
            'bonus_percentage' => $validated['bonus_percentage'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'],
            'metadata' => $validated['metadata'] ?? [],
        ];
    }

    /**
     * @param list<int> $bankAccountIds
     */
    private function syncBankAccounts(RechargeMethod $rechargeMethod, array $bankAccountIds): void
    {
        $syncData = collect($bankAccountIds)
            ->values()
            ->mapWithKeys(fn (int $bankAccountId, int $index): array => [
                $bankAccountId => [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            ])
            ->all();

        $rechargeMethod->bankAccounts()->sync($syncData);
    }
}
