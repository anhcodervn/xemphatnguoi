<?php

namespace App\Providers;

use App\Features\Client\Wallet\Observers\WalletTransactionObserver;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceInterface;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceRegistry;
use App\Models\ApiKey;
use App\Models\QueueLog;
use App\Models\WalletTransaction;
use App\Utils\SendMessage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TrafficFineSourceInterface::class,
            fn (): TrafficFineSourceInterface => $this->app->make(TrafficFineSourceRegistry::class)->resolve(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('vi');
        config(['app.locale' => 'vi']);
        WalletTransaction::observe(WalletTransactionObserver::class);

        RateLimiter::for('traffic-fine-lookup', function (Request $request): Limit {
            return Limit::perMinute((int) config('traffic-fines.rate_limit.per_minute', 20))
                ->by($request->ip() ?: 'unknown')
                ->response(static function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'status' => 'rate_limited',
                        'message' => 'Bạn thao tác quá nhanh. Vui lòng chờ một phút rồi thử lại.',
                    ], 429, $headers);
                });
        });

        RateLimiter::for('n8n-content', function (Request $request): Limit {
            return Limit::perMinute(max(1, (int) config('services.n8n_content.rate_limit_per_minute', 30)))
                ->by($request->ip() ?: 'unknown')
                ->response(static function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'N8N content API rate limit exceeded.',
                    ], 429, $headers);
                });
        });

        Auth::viaRequest('api-key', function (Request $request) {
            $apiKeyValue = trim((string) $request->header('X-API-KEY'));
            $apiSecret = trim((string) $request->header('X-API-SECRET'));

            if ($apiKeyValue === '' || $apiSecret === '') {
                return null;
            }

            $apiKey = ApiKey::query()
                ->with('user')
                ->where('api_key', $apiKeyValue)
                ->first();

            if (! $apiKey instanceof ApiKey) {
                return null;
            }

            $apiKey->markExpiredIfNeeded();

            if (! $apiKey->isActive() || ! $apiKey->matchesSecret($apiSecret) || ! $apiKey->allowsIp($request->ip())) {
                return null;
            }

            $request->attributes->set('apiKey', $apiKey);

            return $apiKey->user;
        });

        Queue::before(function (JobProcessing $event): void {
            $payload = $event->job->payload();
            $jobUuid = $event->job->uuid() ?: Arr::get($payload, 'uuid');

            if ($this->canWriteQueueLogs()) {
                QueueLog::query()->create([
                    'job_uuid' => is_string($jobUuid) ? $jobUuid : null,
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'job_name' => $event->job->resolveName(),
                    'status' => 'processing',
                    'attempts' => (int) $event->job->attempts(),
                    'payload' => $this->sanitizeQueuePayload($payload),
                    'processing_at' => now(),
                ]);
            }
        });

        Queue::after(function (JobProcessed $event): void {
            $jobUuid = $event->job->uuid() ?: Arr::get($event->job->payload(), 'uuid');
            if (! is_string($jobUuid) || $jobUuid === '') {
                $jobUuid = null;
            }

            if ($event->job->isReleased()) {
                if ($this->canWriteQueueLogs() && is_string($jobUuid) && $jobUuid !== '') {
                    QueueLog::query()
                        ->where('job_uuid', $jobUuid)
                        ->where('status', 'processing')
                        ->latest('id')
                        ->limit(1)
                        ->delete();
                }

                return;
            }

            if ($this->canWriteQueueLogs() && is_string($jobUuid) && $jobUuid !== '') {
                QueueLog::query()
                    ->where('job_uuid', $jobUuid)
                    ->where('status', 'processing')
                    ->latest('id')
                    ->limit(1)
                    ->update([
                        'status' => 'success',
                        'processed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        });

        Queue::failing(function (JobFailed $event): void {
            $jobUuid = $event->job->uuid() ?: Arr::get($event->job->payload(), 'uuid');
            $errorMessage = $event->exception->getMessage();

            if ($this->canWriteQueueLogs() && is_string($jobUuid) && $jobUuid !== '') {
                QueueLog::query()
                    ->where('job_uuid', $jobUuid)
                    ->where('status', 'processing')
                    ->latest('id')
                    ->limit(1)
                    ->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'error_message' => $this->truncateError($errorMessage),
                        'updated_at' => now(),
                    ]);

                $this->sendQueueFailedNotification($event);

                return;
            }

            if ($this->canWriteQueueLogs()) {
                QueueLog::query()->create([
                    'job_uuid' => null,
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'job_name' => $event->job->resolveName(),
                    'status' => 'failed',
                    'attempts' => (int) $event->job->attempts(),
                    'payload' => $this->sanitizeQueuePayload($event->job->payload()),
                    'error_message' => $this->truncateError($errorMessage),
                    'processing_at' => now(),
                    'failed_at' => now(),
                ]);
            }

            $this->sendQueueFailedNotification($event);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizeQueuePayload(array $payload): array
    {
        $data = Arr::except($payload, ['data.command']);

        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = Arr::except($data['data'], ['command']);
        }

        return $data;
    }

    private function truncateError(string $message): string
    {
        return mb_strimwidth($message, 0, 4000, '...');
    }

    private function canWriteQueueLogs(): bool
    {
        static $canWrite;

        if (is_bool($canWrite)) {
            return $canWrite;
        }

        try {
            $canWrite = Schema::hasTable((new QueueLog)->getTable());
        } catch (Throwable) {
            $canWrite = false;
        }

        return $canWrite;
    }

    private function sendQueueFailedNotification(JobFailed $event): void
    {
        $payload = $this->sanitizeQueuePayload($event->job->payload());

        SendMessage::sendQueueReport('Task xử lý thất bại', [
            'Job' => $event->job->resolveName(),
            'Queue' => $event->job->getQueue(),
            'Connection' => $event->connectionName,
            'Attempts' => (int) $event->job->attempts(),
            'Job UUID' => $event->job->uuid() ?: Arr::get($payload, 'uuid'),
            'Lý do lỗi' => $this->truncateError($event->exception->getMessage()),
            'Payload' => $payload,
        ]);
    }
}
