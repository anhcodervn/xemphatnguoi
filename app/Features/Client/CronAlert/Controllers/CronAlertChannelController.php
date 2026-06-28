<?php

namespace App\Features\Client\CronAlert\Controllers;

use App\Exceptions\ApiException;
use App\Features\Client\CronAlert\Requests\AlertChannelIndexRequest;
use App\Features\Client\CronAlert\Requests\StoreCronAlertChannelRequest;
use App\Features\Client\CronAlert\Requests\UpdateCronAlertChannelRequest;
use App\Features\Cron\Resources\CronAlertChannelResource;
use App\Features\Cron\Services\CronAlertService;
use App\Features\Cron\Services\CronPlanService;
use App\Http\Controllers\Controller;
use App\Models\CronAlertChannel;
use App\Models\CronJob;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class CronAlertChannelController extends Controller
{
    public function __construct(
        private readonly CronPlanService $cronPlanService,
        private readonly CronAlertService $cronAlertService,
    ) {}

    public function index(AlertChannelIndexRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $perPage = min(max((int) $request->validated('per_page', 15), 1), 100);

        $channels = $user->cronAlertChannels()
            ->when(($request->validated('type') ?? '') !== '', fn ($query) => $query->where('type', $request->validated('type')))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json(ApiResponse::success(data: [
            'data' => CronAlertChannelResource::collection($channels->getCollection())->resolve(),
            'meta' => [
                'current_page' => $channels->currentPage(),
                'last_page' => $channels->lastPage(),
                'per_page' => $channels->perPage(),
                'total' => $channels->total(),
            ],
        ]));
    }

    public function store(StoreCronAlertChannelRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $limits = $this->cronPlanService->limitsForUser($user);
        $this->assertAlertSupport($request->validated(), $limits, $user);

        $channel = $user->cronAlertChannels()->create($request->validated());

        return response()->json(ApiResponse::success(
            message: 'Tạo alert channel thành công.',
            data: ['channel' => CronAlertChannelResource::make($channel)->resolve()],
        ), 201);
    }

    public function show(CronAlertChannel $cronAlertChannel, AlertChannelIndexRequest $request): JsonResponse
    {
        $channel = $this->ownedChannel($cronAlertChannel, $this->user($request));

        return response()->json(ApiResponse::success(data: [
            'channel' => CronAlertChannelResource::make($channel)->resolve(),
        ]));
    }

    public function update(UpdateCronAlertChannelRequest $request, CronAlertChannel $cronAlertChannel): JsonResponse
    {
        $user = $this->user($request);
        $channel = $this->ownedChannel($cronAlertChannel, $user);
        $limits = $this->cronPlanService->limitsForUser($user);
        $this->assertAlertSupport($request->validated(), $limits, $user, $channel);

        $channel->update($request->validated());

        return response()->json(ApiResponse::success(
            message: 'Cập nhật alert channel thành công.',
            data: ['channel' => CronAlertChannelResource::make($channel->fresh())->resolve()],
        ));
    }

    public function destroy(CronAlertChannel $cronAlertChannel, AlertChannelIndexRequest $request): JsonResponse
    {
        $channel = $this->ownedChannel($cronAlertChannel, $this->user($request));
        $channel->delete();

        return response()->json(ApiResponse::success(message: 'Đã xóa alert channel.'));
    }

    public function test(CronAlertChannel $cronAlertChannel, AlertChannelIndexRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $channel = $this->ownedChannel($cronAlertChannel, $user);
        $cronJob = $user->cronJobs()->latest('id')->first() ?? CronJob::make([
            'name' => 'Test Alert',
            'url' => 'https://example.com/health',
            'method' => 'GET',
        ]);

        $this->cronAlertService->sendTest($channel, $cronJob);

        return response()->json(ApiResponse::success(message: 'Đã gửi test alert.'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $limits
     */
    private function assertAlertSupport(array $payload, array $limits, User $user, ?CronAlertChannel $ignore = null): void
    {
        if (! (bool) ($limits['allow_alerts'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ alert channels.', 422);
        }

        $maxAlertChannels = (int) ($limits['max_alert_channels'] ?? 0);
        $count = $user->cronAlertChannels()
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->id))
            ->count();

        if ($count >= $maxAlertChannels) {
            throw new ApiException(sprintf('Bạn đã đạt giới hạn %d alert channels.', $maxAlertChannels), 422);
        }

        $type = (string) ($payload['type'] ?? '');
        if ($type === 'discord' && ! (bool) ($limits['allow_discord_alert'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ Discord alert.', 422);
        }

        if ($type === 'telegram' && ! (bool) ($limits['allow_telegram_alert'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ Telegram alert.', 422);
        }

        if ($type === 'webhook' && ! (bool) ($limits['allow_webhook_alert'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ Webhook alert.', 422);
        }
    }

    private function ownedChannel(CronAlertChannel $channel, User $user): CronAlertChannel
    {
        abort_unless($channel->user_id === $user->id, 404);

        return $channel;
    }

    private function user($request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
