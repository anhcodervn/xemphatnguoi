<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\QueueLog;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('vi');
        config(['app.locale' => 'vi']);

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
        });

        Queue::after(function (JobProcessed $event): void {
            $jobUuid = $event->job->uuid() ?: Arr::get($event->job->payload(), 'uuid');
            if (! is_string($jobUuid) || $jobUuid === '') {
                return;
            }

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
        });

        Queue::failing(function (JobFailed $event): void {
            $jobUuid = $event->job->uuid() ?: Arr::get($event->job->payload(), 'uuid');
            $errorMessage = $event->exception->getMessage();

            if (is_string($jobUuid) && $jobUuid !== '') {
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

                return;
            }

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
        });
    }

    /**
     * @param array<string, mixed> $payload
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
}
