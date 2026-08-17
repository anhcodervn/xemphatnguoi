<?php

namespace App\Features\TrafficFine\Services;

use App\Models\User;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CloudflareTurnstileService
{
    public const STATUS_NOT_REQUIRED = 'not_required';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REQUIRED = 'required';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNAVAILABLE = 'unavailable';

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly TrafficFineTurnstileSettingsService $settings,
        private readonly LicensePlateNormalizer $plateNormalizer,
        private readonly CacheFactory $cache,
    ) {}

    public function verifyPublicLookup(Request $request): string
    {
        $user = $request->user();

        if (! $this->settings->requiresChallenge($user instanceof User ? $user : null)) {
            return self::STATUS_NOT_REQUIRED;
        }

        if (! $this->settings->isConfigured()) {
            return self::STATUS_UNAVAILABLE;
        }

        $token = trim((string) $request->input('cf-turnstile-response', ''));

        if ($token === '') {
            return self::STATUS_REQUIRED;
        }

        if (mb_strlen($token) > 2048) {
            return self::STATUS_FAILED;
        }

        $payload = array_filter([
            'secret' => $this->settings->secretKey(),
            'response' => $token,
            'remoteip' => $request->ip(),
            'idempotency_key' => (string) Str::uuid(),
        ], static fn (mixed $value): bool => filled($value));

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout((int) config('services.turnstile.connect_timeout', 2))
                ->timeout((int) config('services.turnstile.timeout', 5))
                ->withoutRedirecting()
                ->post(self::VERIFY_URL, $payload);
        } catch (ConnectionException) {
            return self::STATUS_UNAVAILABLE;
        }

        if (! $response->successful()) {
            return self::STATUS_UNAVAILABLE;
        }

        $data = $response->json();

        if (! is_array($data) || ! array_key_exists('success', $data) || ! is_bool($data['success'])) {
            return self::STATUS_UNAVAILABLE;
        }

        if ($data['success'] !== true || ! $this->responseContextMatches($data)) {
            return self::STATUS_FAILED;
        }

        $this->grantResultAccess($request);

        return self::STATUS_VERIFIED;
    }

    public function mayViewResult(Request $request, string $plate, string $vehicleType): bool
    {
        $user = $request->user();

        if (! $this->settings->requiresChallenge($user instanceof User ? $user : null)) {
            return true;
        }

        try {
            return $this->cacheStore()->has($this->resultGrantKey($request, $plate, $vehicleType));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function responseContextMatches(array $data): bool
    {
        $expectedAction = (string) config('services.turnstile.action', 'traffic_fine_lookup');
        $expectedHostname = mb_strtolower(trim((string) config('services.turnstile.hostname', '')));
        $action = is_string($data['action'] ?? null) ? $data['action'] : '';
        $hostname = is_string($data['hostname'] ?? null) ? mb_strtolower($data['hostname']) : '';

        if ($action !== $expectedAction) {
            return false;
        }

        return $expectedHostname === '' || hash_equals($expectedHostname, $hostname);
    }

    private function grantResultAccess(Request $request): void
    {
        try {
            $this->cacheStore()->put(
                $this->resultGrantKey(
                    $request,
                    $request->string('plate')->toString(),
                    $request->string('vehicle_type')->toString(),
                ),
                true,
                (int) config('services.turnstile.grant_ttl', 300),
            );
        } catch (Throwable) {
            return;
        }
    }

    private function resultGrantKey(Request $request, string $plate, string $vehicleType): string
    {
        $normalizedPlate = $this->plateNormalizer->normalize($plate);
        $user = $request->user();
        $visitor = $user instanceof User ? 'user:'.$user->getKey() : 'guest';
        $fingerprint = implode('|', [$visitor, $request->ip() ?: 'unknown', $normalizedPlate, $vehicleType]);

        return 'traffic_fine_turnstile_grant:'.hash('sha256', $fingerprint);
    }

    private function cacheStore(): Repository
    {
        return $this->cache->store((string) config('traffic-fines.cache.store', 'redis'));
    }
}
