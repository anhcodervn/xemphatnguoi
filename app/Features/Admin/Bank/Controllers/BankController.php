<?php

namespace App\Features\Admin\Bank\Controllers;

use App\Features\Admin\Bank\Requests\UpdateBankRequest;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $banks = Bank::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($bankQuery) use ($search): void {
                    $bankQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('short_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => true,
            'data' => [
                'banks' => $banks,
            ],
        ]);
    }

    public function update(UpdateBankRequest $request, Bank $bank): JsonResponse
    {
        $validated = $request->validated();

        $bank->fill([
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
}
