<?php

namespace App\Features\Admin\Queue\Controllers;

use App\Features\Admin\Queue\Actions\DeleteFailedQueueJobAction;
use App\Features\Admin\Queue\Actions\GetQueueOverviewAction;
use App\Features\Admin\Queue\Actions\ListFailedQueueJobsAction;
use App\Features\Admin\Queue\Actions\ListQueueLogsAction;
use App\Features\Admin\Queue\Actions\RetryFailedQueueJobAction;
use App\Features\Admin\Queue\Requests\QueueFailedJobIndexRequest;
use App\Features\Admin\Queue\Requests\QueueLogIndexRequest;
use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class QueueController extends Controller
{
    public function overview(GetQueueOverviewAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle()));
    }

    public function logs(QueueLogIndexRequest $request, ListQueueLogsAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function failedJobs(QueueFailedJobIndexRequest $request, ListFailedQueueJobsAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function retryFailedJob(int $id, RetryFailedQueueJobAction $action): JsonResponse
    {
        $action->handle($id);

        return response()->json(ApiResponse::success(message: 'Đã đưa job lỗi vào hàng đợi chạy lại.'));
    }

    public function deleteFailedJob(int $id, DeleteFailedQueueJobAction $action): JsonResponse
    {
        $action->handle($id);

        return response()->json(ApiResponse::success(message: 'Đã xóa failed job.'));
    }
}
