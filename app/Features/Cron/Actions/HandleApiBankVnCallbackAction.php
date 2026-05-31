<?php

namespace App\Features\Cron\Actions;

use App\Features\Cron\Services\ApiBankVnCallbackService;
use App\Models\RechargeOrder;
use Illuminate\Http\Request;

class HandleApiBankVnCallbackAction
{
    public function __construct(
        private readonly ApiBankVnCallbackService $callbackService,
    ) {}

    /**
     * @return array{status_code:int,body:array<string,mixed>}
     */
    public function handle(Request $request): array
    {
        $payload = $request->all();

        $verification = $this->callbackService->verifyCallbackSignature(
            $payload,
            $request->header('X-Webhook-Secret'),
        );

        if (! $verification['ok']) {
            return [
                'status_code' => $verification['status_code'],
                'body' => [
                    'status' => false,
                    'message' => $verification['message'],
                    'data' => $verification['data'],
                ],
            ];
        }

        $description = $this->callbackService->extractTransactionDescription($payload);

        if ($description === '') {
            return $this->ignoredResponse(
                'Không tìm thấy nội dung giao dịch để đối soát.',
                'missing_description',
            );
        }

        if ($this->callbackService->transactionIsOutgoing($payload)) {
            return $this->ignoredResponse(
                'Giao dịch tiền ra được bỏ qua.',
                'outgoing_transaction',
            );
        }

        $callbackAmount = $this->callbackService->extractTransactionAmount($payload);

        if ($callbackAmount === null) {
            return $this->ignoredResponse(
                'Không tìm thấy số tiền giao dịch để đối soát.',
                'missing_amount',
            );
        }

        $order = $this->callbackService->findMatchingRechargeOrder(
            description: $description,
            amount: $callbackAmount,
        );

        if (! $order instanceof RechargeOrder) {
            return [
                'status_code' => 200,
                'body' => [
                    'status' => true,
                    'message' => 'Không có lệnh nạp phù hợp với nội dung và số tiền giao dịch.',
                    'data' => [
                        'ignored' => true,
                        'reason' => 'order_not_found',
                        'description' => $description,
                        'amount' => $callbackAmount,
                    ],
                ],
            ];
        }

        return $this->callbackService->approveRechargeOrder(
            orderId: $order->id,
            payload: $payload,
            description: $description,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}
     */
    private function ignoredResponse(string $message, string $reason): array
    {
        return [
            'status_code' => 200,
            'body' => [
                'status' => true,
                'message' => $message,
                'data' => [
                    'ignored' => true,
                    'reason' => $reason,
                ],
            ],
        ];
    }
}
