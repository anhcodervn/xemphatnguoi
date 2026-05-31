<?php

namespace App\Features\Auth\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleAuthService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled($this->redirectUri());
    }

    public function generateState(): string
    {
        return Str::random(40);
    }

    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => (string) config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    /**
     * @return array{
     *     id:string,
     *     email:string,
     *     name:string,
     *     avatar:?string,
     *     email_verified:bool
     * }
     */
    public function fetchUser(string $code): array
    {
        $tokenResponse = $this->http->asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => (string) config('services.google.client_id'),
            'client_secret' => (string) config('services.google.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if ($tokenResponse->failed()) {
            throw new RuntimeException('Không thể xác thực với Google. Vui lòng thử lại.');
        }

        $accessToken = $tokenResponse->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google không trả về access token hợp lệ.');
        }

        $profileResponse = $this->http
            ->withToken($accessToken)
            ->acceptJson()
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($profileResponse->failed()) {
            throw new RuntimeException('Không thể lấy thông tin tài khoản Google.');
        }

        $payload = $profileResponse->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Dữ liệu tài khoản Google không hợp lệ.');
        }

        $email = Arr::get($payload, 'email');
        $googleId = Arr::get($payload, 'sub');

        if (! is_string($email) || $email === '' || ! is_string($googleId) || $googleId === '') {
            throw new RuntimeException('Tài khoản Google không cung cấp đủ email hoặc mã định danh.');
        }

        return [
            'id' => $googleId,
            'email' => Str::lower($email),
            'name' => (string) (Arr::get($payload, 'name') ?: Str::before($email, '@')),
            'avatar' => Arr::get($payload, 'picture'),
            'email_verified' => (bool) Arr::get($payload, 'email_verified', false),
        ];
    }

    private function redirectUri(): string
    {
        $configured = (string) config('services.google.redirect');

        if ($configured !== '') {
            return $configured;
        }

        return route('auth.google.callback');
    }
}
