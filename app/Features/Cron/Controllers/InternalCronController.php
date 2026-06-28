<?php

namespace App\Features\Cron\Controllers;

use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class InternalCronController extends Controller
{
    public function dispatchDue(Request $request): JsonResponse
    {
        if ($response = $this->ensureAuthorized($request)) {
            return $response;
        }

        $payload = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        return $this->runCommand('cron:dispatch-due', [
            '--limit' => $payload['limit'] ?? 200,
        ]);
    }

    public function pruneLogs(Request $request): JsonResponse
    {
        if ($response = $this->ensureAuthorized($request)) {
            return $response;
        }

        return $this->runCommand('cron:prune-logs');
    }

    public function recalculateNextRun(Request $request): JsonResponse
    {
        if ($response = $this->ensureAuthorized($request)) {
            return $response;
        }

        $payload = $request->validate([
            'only_missing' => ['nullable', 'boolean'],
        ]);

        $parameters = [];

        if ((bool) ($payload['only_missing'] ?? false)) {
            $parameters['--only-missing'] = true;
        }

        return $this->runCommand('cron:recalculate-next-run', $parameters);
    }

    public function resetUsageQuota(Request $request): JsonResponse
    {
        if ($response = $this->ensureAuthorized($request)) {
            return $response;
        }

        $payload = $request->validate([
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $parameters = [];

        if (array_key_exists('retention_days', $payload) && $payload['retention_days'] !== null) {
            $parameters['--retention-days'] = $payload['retention_days'];
        }

        return $this->runCommand('cron:reset-usage-quota', $parameters);
    }

    private function ensureAuthorized(Request $request): ?JsonResponse
    {
        $expectedKey = trim((string) config('services.internal_cron.key', ''));

        if ($expectedKey === '') {
            return response()->json(ApiResponse::error('AUTOCRON_INTERNAL_KEY chưa được cấu hình.'), 503);
        }

        $providedKey = trim((string) $request->header('X-CRON-KEY'));

        if (! hash_equals($expectedKey, $providedKey)) {
            return response()->json(ApiResponse::error('Cron key không hợp lệ.'), 403);
        }

        return null;
    }

    private function runCommand(string $command, array $parameters = []): JsonResponse
    {
        $exitCode = Artisan::call($command, $parameters);

        return response()->json(ApiResponse::success(data: [
            'command' => $command,
            'parameters' => $parameters,
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
        ]));
    }
}
