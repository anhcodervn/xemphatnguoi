<?php

namespace App\Features\Captcha\Services;

use App\Exceptions\ApiException;
use App\Features\Captcha\Resources\CaptchaTaskResource;
use App\Features\Client\Package\Services\PackageService;
use App\Models\ApiKey;
use App\Models\CaptchaService;
use App\Models\CaptchaSource;
use App\Models\CaptchaTask;
use App\Models\User;
use App\Service\Captcha\AutoCaptchaPro;
use App\Service\Captcha\Captcha69;
use App\Service\Captcha\MbbCaptcha;
use App\Service\Captcha\VcbCaptcha;
use App\Service\DiscordWebhookNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CaptchaTaskService
{
    public function __construct(
        private readonly PackageService $packageService,
        private readonly DiscordWebhookNotifier $discordWebhookNotifier,
    ) {}

    public function adminTaskList(Request $request): array
    {
        $tasks = CaptchaTask::query()
            ->with(['user:id,username,full_name,email', 'service:id,name,code', 'source:id,name'])
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return [
            'tasks' => [
                ...$tasks->toArray(),
                'data' => CaptchaTaskResource::collection($tasks->getCollection())->resolve(),
            ],
        ];
    }

    public function clientOverview(User $user): array
    {
        $taskQuery = $user->captchaTasks();

        return [
            'summary' => [
                'total_tasks' => (clone $taskQuery)->count(),
                'pending_tasks' => (clone $taskQuery)->where('status', CaptchaTask::STATUS_PENDING)->count(),
                'solved_tasks' => (clone $taskQuery)->where('status', CaptchaTask::STATUS_SOLVED)->count(),
                'failed_tasks' => (clone $taskQuery)->where('status', CaptchaTask::STATUS_FAILED)->count(),
                'spent' => (float) ((clone $taskQuery)->where('billing_source', 'wallet')->sum('selling_price')),
            ],
            'recent_tasks' => CaptchaTaskResource::collection(
                $user->captchaTasks()->with('service')->latest('id')->limit(8)->get()
            )->resolve(),
        ];
    }

    public function clientTaskList(User $user, Request $request): array
    {
        $tasks = $user->captchaTasks()
            ->with('service')
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->filled('service_code'),
                fn ($query) => $query->where('service_code', $request->string('service_code')->toString())
            )
            ->when(
                $request->filled('task_code'),
                fn ($query) => $query->where('task_code', 'like', '%'.$request->string('task_code')->toString().'%')
            )
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return [
            'tasks' => [
                ...$tasks->toArray(),
                'data' => CaptchaTaskResource::collection($tasks->getCollection())->resolve(),
            ],
        ];
    }

    public function apiBalance(User $user): array
    {
        $wallet = $user->wallet;

        return [
            'balance' => (string) ($wallet?->balance ?? 0),
            'hold_balance' => (string) ($wallet?->hold_balance ?? 0),
        ];
    }

    public function apiUserInfo(User $user, ApiKey $apiKey): array
    {
        $wallet = $user->wallet;
        $taskQuery = $user->captchaTasks();
        $activePackages = $this->packageService->getActiveUserSubscriptionsInfo($user);
        $subscription = $apiKey->isPackageKey()
            ? $this->packageService->resolveSubscriptionFromApiKey($apiKey)
            : null;
        $packageSubscriptionInfo = $subscription
            ? collect($activePackages)->firstWhere('id', $subscription->id)
            : null;

        return [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->role,
                'created_at' => $user->created_at?->toISOString(),
            ],
            'wallet' => [
                'balance' => (string) ($wallet?->balance ?? 0),
                'hold_balance' => (string) ($wallet?->hold_balance ?? 0),
                'total_recharge' => (string) ($wallet?->total_recharge ?? 0),
                'total_spent' => (string) ($wallet?->total_spent ?? 0),
            ],
            'current_package' => $this->packageService->getCurrentUserSubscriptionInfo($user),
            'active_packages' => $activePackages,
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'api_key' => $apiKey->api_key,
                'key_type' => $apiKey->key_type,
                'status' => $apiKey->status,
                'permissions' => $apiKey->permissions ?? [],
                'ip_whitelist' => $apiKey->ip_whitelist ?? [],
                'package_subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'package_id' => $subscription->package_id,
                    'package_name' => $subscription->package_name,
                    'expires_at' => $subscription->expires_at?->toISOString(),
                    'remaining_captcha_quota' => $packageSubscriptionInfo['remaining_captcha_quota'] ?? null,
                ] : null,
                'last_used_at' => $apiKey->last_used_at?->toISOString(),
                'expired_at' => $apiKey->expired_at?->toISOString(),
                'created_at' => $apiKey->created_at?->toISOString(),
            ],
            'task_stats' => [
                'total_tasks' => (clone $taskQuery)->count(),
                'pending_tasks' => (clone $taskQuery)->where('status', CaptchaTask::STATUS_PENDING)->count(),
                'solved_tasks' => (clone $taskQuery)->where('status', CaptchaTask::STATUS_SOLVED)->count(),
                'failed_tasks' => (clone $taskQuery)->where('status', CaptchaTask::STATUS_FAILED)->count(),
            ],
        ];
    }

    public function createTask(User $user, ApiKey $apiKey, array $payload): array
    {
        $service = CaptchaService::query()
            ->with('source')
            ->where('code', $payload['service_code'])
            ->where('is_active', true)
            ->firstOrFail();

        $task = $this->createTaskRecord($user, $apiKey, $service, $payload);

        return $this->formatTaskResponse($task);
    }

    /**
     * @param  array{mode?:string,subscription_id?:int|null,quota_consumed?:int}  $billingContext
     */
    public function createTaskRecord(User $user, ApiKey $apiKey, CaptchaService $service, array $payload, array $billingContext = []): CaptchaTask
    {
        $providerResult = $this->resolveProviderResult($service, $payload);

        $task = CaptchaTask::query()->create([
            'user_id' => $user->id,
            'api_key_id' => $apiKey->id,
            'captcha_service_id' => $service->id,
            'captcha_source_id' => $service->default_source_id,
            'task_code' => 'ct_'.Str::lower(Str::random(24)),
            'external_task_id' => $providerResult['external_task_id'],
            'service_code' => $service->code,
            'status' => $providerResult['status'],
            'request_payload' => [
                'task' => $payload['task'],
                'callback_url' => $payload['callback_url'] ?? null,
                'soft_id' => $payload['soft_id'] ?? null,
            ],
            'result_payload' => $providerResult['result_payload'],
            'provider_cost' => $service->base_price,
            'selling_price' => $service->selling_price,
            'billing_source' => (string) ($billingContext['mode'] ?? 'wallet'),
            'package_subscription_id' => $billingContext['subscription_id'] ?? null,
            'package_quota_consumed' => (int) ($billingContext['quota_consumed'] ?? 0),
            'error_message' => $providerResult['error_message'],
            'requested_at' => now(),
            'solved_at' => $providerResult['solved_at'],
        ]);

        $task->load(['service', 'source']);

        return $task;
    }

    public function formatTaskResponse(CaptchaTask $task): array
    {
        return [
            'task_code' => $task->task_code,
            'status' => $task->status,
            'message' => $task->status === CaptchaTask::STATUS_SOLVED
                ? 'Captcha đã được giải ngay.'
                : 'Task đã được tạo, vui lòng kiểm tra lại sau.',
            'task' => CaptchaTaskResource::make($task)->resolve(),
        ];
    }

    public function formatCreatedTaskResponse(CaptchaTask $task): array
    {
        return [
            'task_code' => $task->task_code,
            'task_data' => $task->request_payload['task'] ?? [],
        ];
    }

    public function showTask(User $user, string $taskCode): array
    {
        $task = $user->captchaTasks()
            ->with(['service', 'source'])
            ->where('task_code', $taskCode)
            ->firstOrFail();

        $task = $this->refreshTaskIfNeeded($task);
        $this->attachApiLogServiceResponse($task);

        return [
            'task_code' => $task->task_code,
            'captcha' => $this->extractPublicCaptchaValue($task->result_payload),
        ];
    }

    public function attachApiLogServiceResponse(CaptchaTask $task): void
    {
        if (! app()->bound('request')) {
            return;
        }

        $task->loadMissing(['service', 'source']);

        $responsePayload = $task->result_payload;

        if (! is_array($responsePayload) || $responsePayload === []) {
            $responsePayload = [
                'external_task_id' => $task->external_task_id,
                'status' => $task->status,
                'error_message' => $task->error_message,
            ];
        }

        $this->pushServiceResponseData([
            'driver' => $task->source?->driver ?? 'unknown',
            'action' => $task->service_code,
            'task_code' => $task->task_code,
            'request' => is_array($task->request_payload['task'] ?? null) ? $task->request_payload['task'] : [],
            'response' => $responsePayload,
            'logged_at' => now()->toISOString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    public function attachApiLogServiceException(string $driver, string $action, array $requestPayload, ApiException $exception): void
    {
        $this->pushServiceResponseData([
            'driver' => $driver,
            'action' => $action,
            'request' => $requestPayload,
            'response' => [
                'status' => false,
                'message' => $exception->getMessage(),
                ...$exception->getData(),
            ],
            'logged_at' => now()->toISOString(),
        ]);
    }

    private function extractPublicCaptchaValue(mixed $resultPayload): string|int|float|null
    {
        if (is_string($resultPayload) || is_int($resultPayload) || is_float($resultPayload)) {
            return $resultPayload;
        }

        if (! is_array($resultPayload)) {
            return null;
        }

        $preferredKeys = [
            'text',
            'token',
            'captcha_output',
            'validate',
            'seccode',
            'challenge',
            'pass_token',
            'clearance',
        ];

        foreach ($preferredKeys as $key) {
            $value = Arr::get($resultPayload, $key);

            if (is_string($value) || is_int($value) || is_float($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array{service_code:string,task:array,callback_url?:string|null,soft_id?:string|null}  $payload
     * @return array{
     *     external_task_id:?string,
     *     status:string,
     *     result_payload:?array,
     *     error_message:?string,
     *     solved_at:Carbon|null
     * }
     */
    private function resolveProviderResult(CaptchaService $service, array $payload): array
    {
        $source = $service->source;

        if (! $source instanceof CaptchaSource || ! $source->is_active) {
            throw new ApiException('Cụm xử lý captcha chưa sẵn sàng hoặc đã bị tắt.', 422);
        }

        if ($source->driver === 'manual') {
            return [
                'external_task_id' => null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    'text' => 'demo-solved-captcha',
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        return match ($source->driver) {
            'autocaptchapro' => $this->resolveAutoCaptchaProResult($service, $payload),
            'captcha69' => $this->resolveCaptcha69Result($service, $payload),
            'mbbcaptcha' => $this->resolveMbbCaptchaResult($service, $payload),
            'vcbcaptcha' => $this->resolveVcbCaptchaResult($service, $payload),
            default => throw new ApiException('Cấu hình xử lý captcha chưa hỗ trợ driver này.', 422),
        };
    }

    private function refreshTaskIfNeeded(CaptchaTask $task): CaptchaTask
    {
        if (! in_array($task->status, [CaptchaTask::STATUS_PENDING, CaptchaTask::STATUS_PROCESSING], true)) {
            return $task;
        }

        $source = $task->source;

        if (! $source instanceof CaptchaSource || ! is_string($task->external_task_id) || trim($task->external_task_id) === '') {
            return $task;
        }

        return match ($source->driver) {
            'captcha69' => $this->refreshCaptcha69TaskResult($task),
            default => $task,
        };
    }

    /**
     * @param  array{service_code:string,task:array,callback_url?:string|null,soft_id?:string|null}  $payload
     * @return array{
     *     external_task_id:?string,
     *     status:string,
     *     result_payload:?array,
     *     error_message:?string,
     *     solved_at:Carbon|null
     * }
     */
    private function resolveAutoCaptchaProResult(CaptchaService $service, array $payload): array
    {
        $source = $service->source;

        if (! $source instanceof CaptchaSource) {
            throw new ApiException('Cụm xử lý captcha chưa sẵn sàng, vui lòng thử lại sau.', 422);
        }

        $provider = new AutoCaptchaPro(
            credentials: is_array($source->credentials) ? $source->credentials : [],
            apiBaseUrl: $source->api_base_url ?: null,
        );

        $taskData = is_array($payload['task']) ? $payload['task'] : [];
        $providerCode = Str::lower((string) ($service->provider_service_code ?: $service->code));

        $response = match ($providerCode) {
            'image-base64', 'imagetotext', 'image-to-text', 'image_to_text' => $provider->imageToText(
                image: (string) (Arr::get($taskData, 'body') ?: Arr::get($taskData, 'image', '')),
                caseSensitive: (bool) Arr::get($taskData, 'case_sensitive', false),
            ),
            'recaptcha-v2', 'recaptcha-v2-token', 'recaptchav2' => $provider->recaptchaV2(
                siteKey: (string) Arr::get($taskData, 'website_key', ''),
                pageUrl: (string) Arr::get($taskData, 'website_url', ''),
            ),
            'recaptcha-v2-invisible', 'recaptcha-v2-invi', 'recaptchav2invi' => $provider->recaptchaV2(
                siteKey: (string) Arr::get($taskData, 'website_key', ''),
                pageUrl: (string) Arr::get($taskData, 'website_url', ''),
                invisible: true,
            ),
            'recaptcha-v3', 'recaptcha-v3-token', 'recaptchav3' => $provider->recaptchaV3(
                siteKey: (string) Arr::get($taskData, 'website_key', ''),
                pageUrl: (string) Arr::get($taskData, 'website_url', ''),
                action: Arr::get($taskData, 'action'),
                proxy: Arr::get($taskData, 'proxy'),
                score: Arr::has($taskData, 'min_score') ? (float) Arr::get($taskData, 'min_score') : null,
            ),
            'turnstile-token', 'cloudflare-turnstile', 'cloudflare', 'turnstile' => $provider->cloudflare(
                siteKey: (string) Arr::get($taskData, 'website_key', ''),
                pageUrl: (string) Arr::get($taskData, 'website_url', ''),
            ),
            'geetest-v4', 'geetestv4' => $provider->geetestV4(
                captchaId: (string) Arr::get($taskData, 'captcha_id', Arr::get($taskData, 'captchaId', '')),
                pageUrl: (string) Arr::get($taskData, 'website_url', ''),
            ),
            default => throw new ApiException('Dịch vụ captcha chưa có mapping xử lý phù hợp.', 422),
        };

        $captcha = Arr::get($response, 'captcha');
        $solution = Arr::get($response, 'solution');
        $externalTaskId = Arr::get($response, 'taskId')
            ?? Arr::get($response, 'task_id')
            ?? Arr::get($response, 'id');

        if (is_array($solution) && $solution !== []) {
            return [
                'external_task_id' => is_scalar($externalTaskId) ? (string) $externalTaskId : null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    ...$solution,
                    'provider_message' => Arr::get($response, 'message'),
                    'raw' => $response,
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        if (is_string($captcha) && $captcha !== '') {
            return [
                'external_task_id' => is_scalar($externalTaskId) ? (string) $externalTaskId : null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    'text' => $captcha,
                    'provider_message' => Arr::get($response, 'message'),
                    'raw' => $response,
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        if (is_array($captcha) && $captcha !== []) {
            return [
                'external_task_id' => is_scalar($externalTaskId) ? (string) $externalTaskId : null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    'raw' => $captcha,
                    'provider_message' => Arr::get($response, 'message'),
                    'response' => $response,
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        return [
            'external_task_id' => is_scalar($externalTaskId) ? (string) $externalTaskId : 'ext_'.Str::lower(Str::random(16)),
            'status' => CaptchaTask::STATUS_PENDING,
            'result_payload' => null,
            'error_message' => null,
            'solved_at' => null,
        ];
    }

    /**
     * @param  array{service_code:string,task:array,callback_url?:string|null,soft_id?:string|null}  $payload
     * @return array{
     *     external_task_id:?string,
     *     status:string,
     *     result_payload:?array,
     *     error_message:?string,
     *     solved_at:Carbon|null
     * }
     */
    private function resolveCaptcha69Result(CaptchaService $service, array $payload): array
    {
        $source = $service->source;

        if (! $source instanceof CaptchaSource) {
            throw new ApiException('Cụm xử lý captcha chưa sẵn sàng, vui lòng thử lại sau.', 422);
        }

        $provider = new Captcha69(
            credentials: is_array($source->credentials) ? $source->credentials : [],
            apiBaseUrl: $source->api_base_url ?: null,
        );

        $taskData = is_array($payload['task']) ? $payload['task'] : [];
        $providerCode = Str::lower((string) ($service->provider_service_code ?: $service->code));

        $response = match ($providerCode) {
            'turnstile-token', 'cloudflare-turnstile', 'turnstile', 'turnstile-task' => $provider->createTurnstileTask(
                websiteUrl: (string) Arr::get($taskData, 'website_url', ''),
                websiteKey: (string) Arr::get($taskData, 'website_key', ''),
                userAgent: Arr::get($taskData, 'user_agent'),
                pageAction: Arr::get($taskData, 'page_action', Arr::get($taskData, 'action')),
                data: Arr::get($taskData, 'data'),
                proxy: is_array(Arr::get($taskData, 'proxy')) ? Arr::get($taskData, 'proxy') : null,
            ),
            default => throw new ApiException('Dịch vụ captcha chưa có mapping xử lý phù hợp.', 422),
        };

        $externalTaskId = Arr::get($response, 'taskId');

        if (! is_scalar($externalTaskId) || trim((string) $externalTaskId) === '') {
            throw new ApiException('Nguồn xử lý captcha không trả về mã task hợp lệ.', 502, [
                'upstream_response' => $response,
            ]);
        }

        return [
            'external_task_id' => (string) $externalTaskId,
            'status' => CaptchaTask::STATUS_PENDING,
            'result_payload' => null,
            'error_message' => null,
            'solved_at' => null,
        ];
    }

    private function refreshCaptcha69TaskResult(CaptchaTask $task): CaptchaTask
    {
        $task->loadMissing(['service', 'source']);

        $source = $task->source;

        if (! $source instanceof CaptchaSource || ! is_string($task->external_task_id) || trim($task->external_task_id) === '') {
            return $task;
        }

        $provider = new Captcha69(
            credentials: is_array($source->credentials) ? $source->credentials : [],
            apiBaseUrl: $source->api_base_url ?: null,
        );

        try {
            $response = $provider->getTaskResult($task->external_task_id);
        } catch (ApiException $exception) {
            if ($exception->getCode() !== 422) {
                return $task;
            }

            $task->forceFill([
                'status' => CaptchaTask::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'solved_at' => now(),
            ])->save();

            $this->notifyFailedTask($task);

            return $task->fresh(['service', 'source']) ?? $task;
        }

        $status = Str::lower((string) Arr::get($response, 'status', 'processing'));

        if ($status !== 'ready') {
            $task->forceFill([
                'status' => in_array($status, ['failed', 'error'], true) ? CaptchaTask::STATUS_FAILED : CaptchaTask::STATUS_PENDING,
                'error_message' => in_array($status, ['failed', 'error'], true)
                    ? (string) (Arr::get($response, 'errorDescription') ?: 'Task captcha xử lý thất bại.')
                    : null,
                'solved_at' => in_array($status, ['failed', 'error'], true) ? now() : null,
            ])->save();

            if (in_array($status, ['failed', 'error'], true)) {
                $this->notifyFailedTask($task);
            }

            return $task->fresh(['service', 'source']) ?? $task;
        }

        $solution = Arr::get($response, 'solution');
        $token = Arr::get($solution, 'token');

        if (! is_string($token) || trim($token) === '') {
            $task->forceFill([
                'status' => CaptchaTask::STATUS_FAILED,
                'error_message' => 'Task captcha đã hoàn tất nhưng không nhận được dữ liệu hợp lệ.',
                'solved_at' => now(),
            ])->save();

            $this->notifyFailedTask($task);

            return $task->fresh(['service', 'source']) ?? $task;
        }

        $task->forceFill([
            'status' => CaptchaTask::STATUS_SOLVED,
            'result_payload' => [
                'token' => trim($token),
                'raw' => $response,
            ],
            'error_message' => null,
            'solved_at' => now(),
        ])->save();

        return $task->fresh(['service', 'source']) ?? $task;
    }

    private function notifyFailedTask(CaptchaTask $task): void
    {
        try {
            $this->discordWebhookNotifier->sendCaptchaTaskFailed($task->fresh(['user', 'service']) ?? $task);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array{service_code:string,task:array,callback_url?:string|null,soft_id?:string|null}  $payload
     * @return array{
     *     external_task_id:?string,
     *     status:string,
     *     result_payload:?array,
     *     error_message:?string,
     *     solved_at:Carbon|null
     * }
     */
    private function resolveVcbCaptchaResult(CaptchaService $service, array $payload): array
    {
        $source = $service->source;

        if (! $source instanceof CaptchaSource) {
            throw new ApiException('Cụm xử lý captcha chưa sẵn sàng, vui lòng thử lại sau.', 422);
        }

        $provider = new VcbCaptcha(
            apiBaseUrl: $source->api_base_url ?: null,
        );

        $taskData = is_array($payload['task']) ? $payload['task'] : [];
        $base64 = (string) (
            Arr::get($taskData, 'base64')
            ?: Arr::get($taskData, 'body')
            ?: Arr::get($taskData, 'image')
        );

        $response = $provider->solve($base64);
        $captcha = Arr::get($response, 'captcha');
        $solution = Arr::get($response, 'solution');

        if (is_array($solution) && $solution !== []) {
            return [
                'external_task_id' => null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    ...$solution,
                    'raw' => $response,
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        if (is_string($captcha) && trim($captcha) !== '') {
            return [
                'external_task_id' => null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    'text' => trim($captcha),
                    'raw' => $response,
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        throw new ApiException('Dịch vụ captcha VCB không trả về kết quả hợp lệ.', 502, [
            'upstream_response' => $response,
        ]);
    }

    /**
     * @param  array{service_code:string,task:array,callback_url?:string|null,soft_id?:string|null}  $payload
     * @return array{
     *     external_task_id:?string,
     *     status:string,
     *     result_payload:?array,
     *     error_message:?string,
     *     solved_at:Carbon|null
     * }
     */
    private function resolveMbbCaptchaResult(CaptchaService $service, array $payload): array
    {
        $source = $service->source;

        if (! $source instanceof CaptchaSource) {
            throw new ApiException('Cụm xử lý captcha chưa sẵn sàng, vui lòng thử lại sau.', 422);
        }

        $provider = new MbbCaptcha(
            apiBaseUrl: $source->api_base_url ?: null,
        );

        $taskData = is_array($payload['task']) ? $payload['task'] : [];
        $base64 = (string) (
            Arr::get($taskData, 'base64')
            ?: Arr::get($taskData, 'body')
            ?: Arr::get($taskData, 'image')
        );

        $response = $provider->solve($base64);
        $captcha = Arr::get($response, 'captcha');
        $solution = Arr::get($response, 'solution');

        if (is_array($solution) && $solution !== []) {
            return [
                'external_task_id' => null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    ...$solution,
                    'raw' => $response,
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        if (is_string($captcha) && trim($captcha) !== '') {
            return [
                'external_task_id' => null,
                'status' => CaptchaTask::STATUS_SOLVED,
                'result_payload' => [
                    'text' => trim($captcha),
                    'raw' => $response,
                ],
                'error_message' => null,
                'solved_at' => now(),
            ];
        }

        throw new ApiException('Dịch vụ captcha MBBank không trả về kết quả hợp lệ.', 502, [
            'upstream_response' => $response,
        ]);
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function pushServiceResponseData(array $log): void
    {
        if (! app()->bound('request')) {
            return;
        }

        $request = request();

        if (! $request instanceof Request) {
            return;
        }

        $payload = $request->attributes->get('service_response_data');
        $logs = is_array($payload['logs'] ?? null) ? $payload['logs'] : [];
        $logs[] = $log;

        $request->attributes->set('service_response_data', [
            'logs' => $logs,
        ]);
    }
}
