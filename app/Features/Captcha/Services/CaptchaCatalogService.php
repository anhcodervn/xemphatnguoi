<?php

namespace App\Features\Captcha\Services;

use App\Features\Captcha\Resources\CaptchaServiceResource;
use App\Features\Captcha\Resources\CaptchaSourceResource;
use App\Features\Captcha\Resources\PublicCaptchaServiceResource;
use App\Models\CaptchaService;
use App\Models\CaptchaSource;
use App\Models\CaptchaTask;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CaptchaCatalogService
{
    public function adminSourceList(Request $request): array
    {
        $sources = CaptchaSource::query()
            ->withCount('services')
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 10), 1), 50))
            ->withQueryString();

        return [
            'sources' => [
                ...$sources->toArray(),
                'data' => CaptchaSourceResource::collection($sources->getCollection())->resolve(),
            ],
        ];
    }

    public function adminServiceList(Request $request): array
    {
        $services = CaptchaService::query()
            ->with('source')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(min(max($request->integer('per_page', 10), 1), 50))
            ->withQueryString();

        $services->setCollection($this->attachRecentStats($services->getCollection()));

        return [
            'services' => [
                ...$services->toArray(),
                'data' => CaptchaServiceResource::collection($services->getCollection())->resolve(),
            ],
        ];
    }

    public function storeSource(array $payload): CaptchaSource
    {
        return CaptchaSource::query()->create($payload);
    }

    public function updateSource(CaptchaSource $source, array $payload): CaptchaSource
    {
        $source->update($payload);

        return $source->fresh();
    }

    public function deleteSource(CaptchaSource $source): void
    {
        $source->delete();
    }

    public function storeService(array $payload): CaptchaService
    {
        return CaptchaService::query()->create($payload)->load('source');
    }

    public function updateService(CaptchaService $service, array $payload): CaptchaService
    {
        $service->update($payload);

        return $service->fresh(['source']);
    }

    public function publicServices(): array
    {
        return PublicCaptchaServiceResource::collection($this->attachRecentStats(
            CaptchaService::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        ))->resolve();
    }

    private function attachRecentStats(Collection $services): Collection
    {
        return $services->map(function (CaptchaService $service): CaptchaService {
            $service->setAttribute('recent_stats', $this->recentStatsForService($service));

            return $service;
        });
    }

    private function recentStatsForService(CaptchaService $service): array
    {
        $recentTasks = $service->tasks()
            ->select(['id', 'status', 'requested_at', 'solved_at'])
            ->latest('id')
            ->limit(100)
            ->get();

        $completedTasks = $recentTasks->filter(fn (CaptchaTask $task): bool => in_array($task->status, [
            CaptchaTask::STATUS_SOLVED,
            CaptchaTask::STATUS_FAILED,
        ], true));

        $solvedTasks = $recentTasks->where('status', CaptchaTask::STATUS_SOLVED);
        $durations = $solvedTasks
            ->map(function (CaptchaTask $task): ?int {
                if (! $task->requested_at || ! $task->solved_at) {
                    return null;
                }

                return max(1, $task->requested_at->diffInSeconds($task->solved_at));
            })
            ->filter(fn (?int $seconds): bool => $seconds !== null)
            ->values();

        $sampleSize = $recentTasks->count();
        $completedCount = $completedTasks->count();
        $solvedCount = $solvedTasks->count();
        $minSeconds = $durations->min();
        $maxSeconds = $durations->max();

        return [
            'sample_size' => $sampleSize,
            'completed_sample_size' => $completedCount,
            'success_rate' => $completedCount > 0 ? (int) round(($solvedCount / $completedCount) * 100) : null,
            'avg_processing_seconds' => $durations->isNotEmpty() ? (int) round($durations->avg()) : null,
            'min_processing_seconds' => $minSeconds,
            'max_processing_seconds' => $maxSeconds,
            'processing_time_label' => $this->formatDurationLabel($minSeconds, $maxSeconds),
        ];
    }

    private function formatDurationLabel(?int $minSeconds, ?int $maxSeconds): ?string
    {
        if (! $minSeconds || ! $maxSeconds) {
            return null;
        }

        if ($minSeconds === $maxSeconds) {
            return "{$minSeconds}s";
        }

        return "{$minSeconds}-{$maxSeconds}s";
    }
}
