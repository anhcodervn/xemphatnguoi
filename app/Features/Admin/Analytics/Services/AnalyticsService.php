<?php

namespace App\Features\Admin\Analytics\Services;

use App\Models\ApiLog;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Service\DiscordWebhookNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
        $paymentsQuery = PaymentTransaction::query()
            ->where('status', 'success')
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));
        $apiLogsQuery = ApiLog::query()->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));
        $webhooks = $this->discordWebhookNotifier->configuredWebhooks();

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
                'proxy_revenue' => 0,
                'provider_cost' => 0,
                'gross_profit' => 0,
                'gross_margin' => 0,
                'tasks_total' => 0,
                'tasks_pending' => 0,
                'tasks_solved' => 0,
                'tasks_failed' => 0,
                'task_success_rate' => 0,
                'avg_processing_seconds' => 0,
                'api_requests' => (int) (clone $apiLogsQuery)->count(),
                'api_avg_response_ms' => round((float) ((clone $apiLogsQuery)->avg('response_time_ms') ?? 0), 2),
                'active_webhooks' => collect($webhooks)->where('is_active', true)->count(),
                'configured_webhooks' => count($webhooks),
            ],
            'top_services' => [],
            'daily_overview' => $this->dailyOverview($days, $from),
            'recent_failed_tasks' => [],
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
     * @return array<int, array<string, string|int|float>>
     */
    private function dailyOverview(int $days, ?Carbon $from): array
    {
        $start = $from?->copy() ?? now()->subDays($days - 1)->startOfDay();

        return collect(range(0, max(0, $days - 1)))
            ->map(function (int $offset) use ($start): array {
                $date = $start->copy()->addDays($offset);
                $dateString = $date->toDateString();

                return [
                    'label' => $date->format('d/m'),
                    'users' => (int) User::query()->whereDate('created_at', $dateString)->count(),
                    'deposits' => (float) PaymentTransaction::query()->where('status', 'success')->whereDate('created_at', $dateString)->sum('amount'),
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                    'tasks_solved' => 0,
                ];
            })
            ->all();
    }
}
