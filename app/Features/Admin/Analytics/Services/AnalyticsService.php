<?php

namespace App\Features\Admin\Analytics\Services;

use App\Models\ApiLog;
use App\Models\CaptchaTask;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Service\DiscordWebhookNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function __construct(
        private readonly DiscordWebhookNotifier $discordWebhookNotifier,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $range): array
    {
        $resolvedRange = $this->resolveRange($range);
        $from = $resolvedRange['from'];
        $days = $resolvedRange['days'];

        $usersQuery = User::query()->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));
        $tasksQuery = CaptchaTask::query()->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));
        $paymentsQuery = PaymentTransaction::query()
            ->where('status', 'success')
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));
        $apiLogsQuery = ApiLog::query()->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));

        $totalTasks = (int) (clone $tasksQuery)->count();
        $solvedTasks = (int) (clone $tasksQuery)->where('status', CaptchaTask::STATUS_SOLVED)->count();
        $failedTasks = (int) (clone $tasksQuery)->where('status', CaptchaTask::STATUS_FAILED)->count();
        $completedTasks = $solvedTasks + $failedTasks;
        $captchaRevenue = (float) (clone $tasksQuery)->sum('selling_price');
        $providerCost = (float) (clone $tasksQuery)->sum('provider_cost');
        $grossProfit = $captchaRevenue - $providerCost;

        $webhooks = $this->discordWebhookNotifier->configuredWebhooks();
        $activeWebhooks = collect($webhooks)->where('is_active', true)->count();

        return [
            'range' => $resolvedRange['key'],
            'filters' => [
                'ranges' => [
                    ['label' => 'Hôm nay', 'value' => 'today'],
                    ['label' => '7 ngày', 'value' => '7d'],
                    ['label' => '30 ngày', 'value' => '30d'],
                    ['label' => 'Toàn bộ', 'value' => 'all'],
                ],
            ],
            'summary' => [
                'users_total' => (int) User::query()->count(),
                'users_active' => (int) User::query()->where('status', 'active')->count(),
                'users_new' => (int) (clone $usersQuery)->count(),
                'users_new_today' => (int) User::query()->whereDate('created_at', today())->count(),
                'wallet_balance_total' => (float) Wallet::query()->where('type', Wallet::TYPE_MAIN)->sum('balance'),
                'wallet_recharge_total' => (float) Wallet::query()->where('type', Wallet::TYPE_MAIN)->sum('total_recharge'),
                'wallet_spent_total' => (float) Wallet::query()->where('type', Wallet::TYPE_MAIN)->sum('total_spent'),
                'deposit_success_amount' => (float) (clone $paymentsQuery)->sum('amount'),
                'captcha_revenue' => $captchaRevenue,
                'provider_cost' => $providerCost,
                'gross_profit' => $grossProfit,
                'gross_margin' => $captchaRevenue > 0 ? round(($grossProfit / $captchaRevenue) * 100, 2) : 0,
                'tasks_total' => $totalTasks,
                'tasks_pending' => (int) (clone $tasksQuery)->where('status', CaptchaTask::STATUS_PENDING)->count(),
                'tasks_solved' => $solvedTasks,
                'tasks_failed' => $failedTasks,
                'task_success_rate' => $completedTasks > 0 ? round(($solvedTasks / $completedTasks) * 100, 2) : 0,
                'avg_processing_seconds' => $this->averageProcessingSeconds((clone $tasksQuery)->whereNotNull('solved_at')->get(['requested_at', 'solved_at'])),
                'api_requests' => (int) (clone $apiLogsQuery)->count(),
                'api_avg_response_ms' => round((float) ((clone $apiLogsQuery)->avg('response_time_ms') ?? 0), 2),
                'active_webhooks' => $activeWebhooks,
                'configured_webhooks' => count($webhooks),
            ],
            'top_services' => CaptchaTask::query()
                ->selectRaw('service_code, COUNT(*) as total_tasks, SUM(selling_price) as revenue, SUM(provider_cost) as cost')
                ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
                ->groupBy('service_code')
                ->orderByDesc('revenue')
                ->limit(6)
                ->get()
                ->map(fn (CaptchaTask $task): array => [
                    'service_code' => $task->service_code,
                    'total_tasks' => (int) $task->total_tasks,
                    'revenue' => (float) $task->revenue,
                    'cost' => (float) $task->cost,
                    'profit' => (float) $task->revenue - (float) $task->cost,
                ])
                ->all(),
            'daily_overview' => $this->dailyOverview($days, $from),
            'recent_failed_tasks' => CaptchaTask::query()
                ->with(['user:id,username,full_name,email', 'service:id,code,name'])
                ->where('status', CaptchaTask::STATUS_FAILED)
                ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (CaptchaTask $task): array => [
                    'task_code' => $task->task_code,
                    'service_code' => $task->service_code,
                    'user' => $task->user?->full_name ?: $task->user?->username ?: $task->user?->email,
                    'error_message' => $task->error_message,
                    'created_at' => $task->created_at?->toISOString(),
                ])
                ->all(),
            'recent_recharges' => PaymentTransaction::query()
                ->with('user:id,username,full_name,email')
                ->where('status', 'success')
                ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (PaymentTransaction $payment): array => [
                    'transaction_code' => $payment->transaction_code,
                    'amount' => (float) $payment->amount,
                    'user' => $payment->user?->full_name ?: $payment->user?->username ?: $payment->user?->email,
                    'created_at' => $payment->created_at?->toISOString(),
                ])
                ->all(),
            'discord' => [
                'events' => $this->discordWebhookNotifier->eventOptions(),
                'webhooks' => $webhooks,
            ],
        ];
    }

    /**
     * @param  array{webhook_index:int,event:string}  $payload
     */
    public function testDiscordWebhook(array $payload): void
    {
        $this->discordWebhookNotifier->sendTestWebhook(
            webhookIndex: (int) $payload['webhook_index'],
            eventKey: (string) $payload['event'],
        );
    }

    /**
     * @return array{key:string,from:Carbon|null,days:int}
     */
    private function resolveRange(string $range): array
    {
        return match ($range) {
            'today' => ['key' => 'today', 'from' => now()->startOfDay(), 'days' => 1],
            '30d' => ['key' => '30d', 'from' => now()->subDays(29)->startOfDay(), 'days' => 30],
            'all' => ['key' => 'all', 'from' => null, 'days' => 7],
            default => ['key' => '7d', 'from' => now()->subDays(6)->startOfDay(), 'days' => 7],
        };
    }

    /**
     * @param  Collection<int, CaptchaTask>  $tasks
     */
    private function averageProcessingSeconds(Collection $tasks): float
    {
        if ($tasks->isEmpty()) {
            return 0;
        }

        $seconds = $tasks
            ->filter(fn (CaptchaTask $task): bool => $task->requested_at !== null && $task->solved_at !== null)
            ->map(fn (CaptchaTask $task): int => max(1, $task->requested_at->diffInSeconds($task->solved_at)))
            ->values();

        if ($seconds->isEmpty()) {
            return 0;
        }

        return round($seconds->avg(), 2);
    }

    /**
     * @return array<int, array<string, string|int|float>>
     */
    private function dailyOverview(int $days, ?Carbon $from): array
    {
        $start = $from?->copy() ?? now()->subDays($days - 1)->startOfDay();
        $labels = collect(range(0, max(0, $days - 1)))
            ->map(fn (int $offset): Carbon => $start->copy()->addDays($offset));

        return $labels->map(function (Carbon $date): array {
            $dateString = $date->toDateString();
            $taskQuery = CaptchaTask::query()->whereDate('created_at', $dateString);

            $revenue = (float) (clone $taskQuery)->sum('selling_price');
            $cost = (float) (clone $taskQuery)->sum('provider_cost');

            return [
                'label' => $date->format('d/m'),
                'users' => (int) User::query()->whereDate('created_at', $dateString)->count(),
                'deposits' => (float) PaymentTransaction::query()->where('status', 'success')->whereDate('created_at', $dateString)->sum('amount'),
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $revenue - $cost,
                'tasks_solved' => (int) (clone $taskQuery)->where('status', CaptchaTask::STATUS_SOLVED)->count(),
            ];
        })->all();
    }
}
