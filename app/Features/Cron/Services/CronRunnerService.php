<?php

namespace App\Features\Cron\Services;

use App\Jobs\RunHttpCronJob;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Models\User;
use App\Support\Enums\CronJobLastStatus;
use App\Support\Enums\CronJobLogStatus;
use App\Support\Enums\CronJobStatus;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CronRunnerService
{
    public function __construct(
        private readonly CronPlanService $cronPlanService,
        private readonly CronScheduleService $cronScheduleService,
        private readonly CronUsageService $cronUsageService,
        private readonly HttpTargetValidator $httpTargetValidator,
        private readonly CronAlertService $cronAlertService,
    ) {}

    public function run(CronJob $cronJob, string $runUuid, int $attempt = 1): void
    {
        $cronJob = CronJob::query()
            ->with(['user.userSubscriptions.package', 'alertChannels'])
            ->findOrFail($cronJob->id);

        if ($cronJob->status !== CronJobStatus::Active) {
            return;
        }

        $user = $cronJob->user;
        if (! $user instanceof User) {
            return;
        }

        $limits = $this->cronPlanService->limitsForUser($user);

        if (($quotaReason = $this->cronUsageService->exceedsQuota($user, $limits)) !== null) {
            $this->recordBlocked($cronJob, $quotaReason, $runUuid, $attempt, 'on_fail');

            return;
        }

        $requestBody = $this->requestBodyString($cronJob);

        try {
            $target = $this->httpTargetValidator->validate(
                $cronJob->url,
                is_array($cronJob->headers) ? $cronJob->headers : [],
                $requestBody,
                $limits,
            );
        } catch (Throwable $throwable) {
            $this->recordBlocked($cronJob, $throwable->getMessage(), $runUuid, $attempt, 'on_fail');

            return;
        }

        $previousFailureCount = $cronJob->consecutive_failures;
        $startedAt = now();

        try {
            $response = $this->sendRequest($cronJob, $requestBody, $limits);
            $result = $this->evaluateResponse($cronJob, $response, $limits);

            $log = $this->storeLog($cronJob, [
                'user_id' => $user->id,
                'run_uuid' => $runUuid,
                'attempt' => $attempt,
                'status' => $result['status'],
                'method' => $cronJob->method->value,
                'url' => $cronJob->url,
                'status_code' => $response->status(),
                'duration_ms' => $result['duration_ms'],
                'request_headers' => $cronJob->headers,
                'request_body_preview' => $this->truncatePreview($requestBody, (int) ($limits['max_body_size_kb'] ?? 16)),
                'response_headers' => $response->headers(),
                'response_body_preview' => $result['response_body_preview'],
                'response_size_bytes' => $result['response_size_bytes'],
                'error_message' => $result['error_message'],
                'ip_resolved' => $target['resolved_ips'][0] ?? null,
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);

            $this->cronUsageService->recordRun($user, $result['status'] === CronJobLogStatus::Success);
            $this->updateJobMetrics($cronJob, $log);

            if ($log->status === CronJobLogStatus::Success) {
                if ($previousFailureCount > 0) {
                    $this->cronAlertService->sendForEvent($cronJob, $log, 'on_recovered');
                }

                return;
            }

            $maxRetries = min((int) $cronJob->retry_count, (int) ($limits['max_retries_per_run'] ?? 0));
            if ($attempt <= $maxRetries) {
                RunHttpCronJob::dispatch($cronJob->id, $runUuid, $attempt + 1)
                    ->delay(now()->addSeconds(max(1, (int) $cronJob->retry_delay_seconds)))
                    ->onQueue((string) ($limits['queue_name'] ?? 'cron-default'));

                return;
            }

            $this->cronAlertService->sendForEvent($cronJob, $log, $result['alert_event']);
        } catch (ConnectionException $exception) {
            $log = $this->storeThrowableLog($cronJob, $runUuid, $attempt, CronJobLogStatus::Timeout, $startedAt, $exception, $target['resolved_ips'][0] ?? null);
            $this->cronUsageService->recordRun($user, false);
            $this->updateJobMetrics($cronJob, $log);
            $this->dispatchRetryOrAlert($cronJob, $log, $runUuid, $attempt, $limits, 'on_timeout');
        } catch (RequestException $exception) {
            $log = $this->storeThrowableLog($cronJob, $runUuid, $attempt, CronJobLogStatus::Error, $startedAt, $exception, $target['resolved_ips'][0] ?? null);
            $this->cronUsageService->recordRun($user, false);
            $this->updateJobMetrics($cronJob, $log);
            $this->dispatchRetryOrAlert($cronJob, $log, $runUuid, $attempt, $limits, 'on_fail');
        } catch (Throwable $exception) {
            $log = $this->storeThrowableLog($cronJob, $runUuid, $attempt, CronJobLogStatus::Error, $startedAt, $exception, $target['resolved_ips'][0] ?? null);
            $this->cronUsageService->recordRun($user, false);
            $this->updateJobMetrics($cronJob, $log);
            $this->dispatchRetryOrAlert($cronJob, $log, $runUuid, $attempt, $limits, 'on_fail');
        }
    }

    public function recordBlocked(CronJob $cronJob, string $message, ?string $runUuid = null, int $attempt = 1, string $event = 'on_fail'): void
    {
        $cronJob->loadMissing('user', 'alertChannels');

        $log = $this->storeLog($cronJob, [
            'user_id' => $cronJob->user_id,
            'run_uuid' => $runUuid ?: (string) Str::uuid(),
            'attempt' => $attempt,
            'status' => CronJobLogStatus::Blocked,
            'method' => $cronJob->method->value,
            'url' => $cronJob->url,
            'request_headers' => $cronJob->headers,
            'request_body_preview' => $this->truncatePreview($this->requestBodyString($cronJob), 8),
            'error_message' => $message,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $this->updateJobMetrics($cronJob, $log);
        $this->cronAlertService->sendForEvent($cronJob, $log, $event);
    }

    /**
     * @param  array<string, mixed>  $limits
     * @return array{status:CronJobLogStatus,duration_ms:int,response_body_preview:?string,response_size_bytes:int,error_message:?string,alert_event:string}
     */
    private function evaluateResponse(CronJob $cronJob, Response $response, array $limits): array
    {
        $durationMs = (int) round((float) ($response->handlerStats()['total_time_us'] ?? 0) / 1000);
        $body = $response->body();
        $responseSizeBytes = strlen($body);
        $responsePreview = $this->truncatePreview($body, min((int) $cronJob->max_response_size_kb, (int) ($limits['max_response_size_kb'] ?? 20)));

        $expectedStatusCodes = is_array($cronJob->expected_status_codes) && $cronJob->expected_status_codes !== []
            ? array_map('intval', $cronJob->expected_status_codes)
            : range(200, 299);

        if (! in_array($response->status(), $expectedStatusCodes, true)) {
            return [
                'status' => CronJobLogStatus::Failed,
                'duration_ms' => $durationMs,
                'response_body_preview' => $responsePreview,
                'response_size_bytes' => $responseSizeBytes,
                'error_message' => sprintf('Unexpected status code %s.', $response->status()),
                'alert_event' => 'on_status_code_mismatch',
            ];
        }

        if (
            is_string($cronJob->expected_body_contains)
            && $cronJob->expected_body_contains !== ''
            && ! str_contains($body, $cronJob->expected_body_contains)
        ) {
            return [
                'status' => CronJobLogStatus::Failed,
                'duration_ms' => $durationMs,
                'response_body_preview' => $responsePreview,
                'response_size_bytes' => $responseSizeBytes,
                'error_message' => 'Response body does not contain expected text.',
                'alert_event' => 'on_body_mismatch',
            ];
        }

        if (
            is_string($cronJob->expected_body_not_contains)
            && $cronJob->expected_body_not_contains !== ''
            && str_contains($body, $cronJob->expected_body_not_contains)
        ) {
            return [
                'status' => CronJobLogStatus::Failed,
                'duration_ms' => $durationMs,
                'response_body_preview' => $responsePreview,
                'response_size_bytes' => $responseSizeBytes,
                'error_message' => 'Response body contains blocked text.',
                'alert_event' => 'on_body_mismatch',
            ];
        }

        return [
            'status' => CronJobLogStatus::Success,
            'duration_ms' => $durationMs,
            'response_body_preview' => $responsePreview,
            'response_size_bytes' => $responseSizeBytes,
            'error_message' => null,
            'alert_event' => 'on_fail',
        ];
    }

    /**
     * @param  array<string, mixed>  $limits
     */
    private function sendRequest(CronJob $cronJob, ?string $requestBody, array $limits): Response
    {
        $query = is_array($cronJob->query_params) ? $cronJob->query_params : [];
        $headers = is_array($cronJob->headers) ? $cronJob->headers : [];

        $request = Http::withHeaders($headers)
            ->timeout(min((int) $cronJob->timeout_seconds, (int) ($limits['max_request_timeout_seconds'] ?? 10)))
            ->connectTimeout(min((int) $cronJob->connect_timeout_seconds, (int) ($limits['max_request_timeout_seconds'] ?? 10)))
            ->withOptions([
                'verify' => (bool) $cronJob->verify_ssl,
                'allow_redirects' => $cronJob->follow_redirects
                    ? [
                        'max' => 5,
                        'on_redirect' => function ($request, $response, $uri): void {
                            $this->httpTargetValidator->validateRedirectTarget((string) $uri);
                        },
                    ]
                    : false,
            ]);

        $options = ['query' => $query];

        if ($cronJob->body_type->value === 'json') {
            $options['json'] = $this->decodeBodyToArray($requestBody);
        }

        if ($cronJob->body_type->value === 'form') {
            $options['form_params'] = $this->decodeBodyToArray($requestBody);
        }

        if ($cronJob->body_type->value === 'raw' && is_string($requestBody) && $requestBody !== '') {
            $options['body'] = $requestBody;
        }

        return $request->send($cronJob->method->value, $cronJob->url, $options);
    }

    private function updateJobMetrics(CronJob $cronJob, CronJobLog $log): void
    {
        DB::transaction(function () use ($cronJob, $log): void {
            $freshJob = CronJob::query()->whereKey($cronJob->id)->lockForUpdate()->firstOrFail();
            $status = $log->status;

            $freshJob->forceFill([
                'last_run_at' => $log->finished_at ?? $log->started_at,
                'last_status' => $status instanceof CronJobLogStatus ? CronJobLastStatus::from($status->value) : null,
                'consecutive_failures' => $status === CronJobLogStatus::Success ? 0 : $freshJob->consecutive_failures + 1,
                'total_runs' => $freshJob->total_runs + 1,
                'total_success' => $freshJob->total_success + ($status === CronJobLogStatus::Success ? 1 : 0),
                'total_failed' => $freshJob->total_failed + ($status === CronJobLogStatus::Success ? 0 : 1),
            ])->save();
        });
    }

    private function dispatchRetryOrAlert(CronJob $cronJob, CronJobLog $log, string $runUuid, int $attempt, array $limits, string $finalEvent): void
    {
        $maxRetries = min((int) $cronJob->retry_count, (int) ($limits['max_retries_per_run'] ?? 0));

        if ($attempt <= $maxRetries) {
            RunHttpCronJob::dispatch($cronJob->id, $runUuid, $attempt + 1)
                ->delay(now()->addSeconds(max(1, (int) $cronJob->retry_delay_seconds)))
                ->onQueue((string) ($limits['queue_name'] ?? 'cron-default'));

            return;
        }

        $this->cronAlertService->sendForEvent($cronJob->fresh(['alertChannels', 'user']), $log, $finalEvent);
    }

    private function storeThrowableLog(
        CronJob $cronJob,
        string $runUuid,
        int $attempt,
        CronJobLogStatus $status,
        CarbonInterface $startedAt,
        Throwable $throwable,
        ?string $resolvedIp,
    ): CronJobLog {
        return $this->storeLog($cronJob, [
            'user_id' => $cronJob->user_id,
            'run_uuid' => $runUuid,
            'attempt' => $attempt,
            'status' => $status,
            'method' => $cronJob->method->value,
            'url' => $cronJob->url,
            'request_headers' => $cronJob->headers,
            'request_body_preview' => $this->truncatePreview($this->requestBodyString($cronJob), 8),
            'error_message' => Str::limit($throwable->getMessage(), 1_000, '...'),
            'ip_resolved' => $resolvedIp,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function storeLog(CronJob $cronJob, array $attributes): CronJobLog
    {
        return $cronJob->logs()->create($attributes);
    }

    private function requestBodyString(CronJob $cronJob): ?string
    {
        if ($cronJob->body === null || $cronJob->body === '') {
            return null;
        }

        return is_string($cronJob->body) ? $cronJob->body : json_encode($cronJob->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeBodyToArray(?string $body): array
    {
        if ($body === null || trim($body) === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function truncatePreview(?string $value, int $maxKb): ?string
    {
        if ($value === null) {
            return null;
        }

        $maxBytes = max(1, $maxKb) * 1024;

        return strlen($value) <= $maxBytes ? $value : substr($value, 0, $maxBytes);
    }
}
