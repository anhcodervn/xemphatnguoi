<?php

namespace App\Service\ApiBank;

use App\Models\BankAccount;
use Illuminate\Support\Carbon;

class ACB
{
    public string $clientId = 'iuSuHYVufIUuNIREV0FB9EoLn9kHsDbm';

    /**
     * @var array<string, string>
     */
    private array $URL = [
        'LOGIN' => 'https://apiapp.acb.com.vn/mb/v2/auth/tokens',
        'getBalance' => 'https://apiapp.acb.com.vn/mb/v2/legacy/ss/cs/bankservice/transfers/list/account-payment',
        'GET_TRANS' => 'https://apiapp.acb.com.vn/mb/v2/legacy/ss/cs/bankservice/saving/tx-history?maxRows=20&account=4650511',
    ];

    protected string|false|null $username = false;

    protected string|false|null $password = false;

    protected string|false $proxy = false;

    protected string|false $Authproxy = false;

    protected BankAccount|false|null $bank;

    public function __construct(BankAccount|string|null $user = null)
    {
        if ($user instanceof BankAccount) {
            $this->bank = $user;

            return;
        }

        $query = BankAccount::query()
            ->whereRaw('LOWER(bank_name) = ?', ['acb']);

        if ($user === null) {
            $this->bank = $query->first() ?: false;

            return;
        }

        $this->bank = $query
            ->where('username', $user)
            ->first() ?: false;
    }

    public function load()
    {
        if (! $this->bank instanceof BankAccount) {
            return $this->JsonData('error', 'Không tìm thấy thông tin ngân hàng trên hệ thống.');
        }

        $this->username = $this->bank->username ?: false;
        $this->password = $this->bank->password ?: false;
        $this->proxy = $this->bank->proxy ?: false;

        return null;
    }

    public function login(bool $lsgd = false)
    {
        if ($errorResponse = $this->load()) {
            return $errorResponse;
        }

        $header = [
            'Content-Type: application/json; charset=utf-8',
            'Host: apiapp.acb.com.vn',
        ];

        $data = [
            'clientId' => $this->clientId,
            'username' => $this->username,
            'password' => $this->password,
        ];

        $json = $this->CURL('LOGIN', $header, $data);

        if (! isset($json['accessToken'])) {
            return $this->JsonData('error', 'Đăng nhập thất bại.', $json);
        }

        $this->persistLoginData($json);

        if ($lsgd) {
            return $this->LSGD(20);
        }

        return $this->JsonData('success', 'Đăng nhập thành công.', $json);
    }

    public function refreshToken(): string|false
    {
        $refreshToken = $this->getRefreshTokenValue();

        if (! $refreshToken) {
            return false;
        }

        $header = [
            'Content-Type: application/json; charset=utf-8',
            'Host: apiapp.acb.com.vn',
        ];

        $data = [
            'clientId' => $this->clientId,
            'grantType' => 'refresh_token',
            'refreshToken' => $refreshToken,
        ];

        $json = $this->CURL('LOGIN', $header, $data);

        if (! isset($json['accessToken'])) {
            return false;
        }

        $this->persistLoginData($json, [
            'refresh_token' => $refreshToken,
        ]);

        return $json['accessToken'];
    }

    public function get_balance(string $token): string
    {
        $header = [
            'Content-Type: application/json; charset=utf-8',
            'Host: apiapp.acb.com.vn',
            "Authorization: bearer {$token}",
        ];

        $result = $this->CURL('getBalance', $header, null);

        return json_encode($result) ?: '[]';
    }

    private function getValidToken(): string|false
    {
        if ($errorResponse = $this->load()) {
            return false;
        }

        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            $this->login();

            return $this->getAccessToken();
        }

        $tokenExpiredAt = $this->getTokenExpiredAt();

        if ($tokenExpiredAt && now()->lt($tokenExpiredAt)) {
            return $accessToken;
        }

        $newToken = $this->refreshToken();

        if ($newToken) {
            return $newToken;
        }

        $this->login();

        return $this->getAccessToken();
    }

    public function LSGD(int $rows = 10)
    {
        if ($errorResponse = $this->load()) {
            return $errorResponse;
        }

        $token = $this->getValidToken();
        $accountNumber = $this->bank?->account_number;

        $header = [
            'Content-Type: application/json;',
            'Host: apiapp.acb.com.vn',
            "Authorization: bearer {$token}",
            'User-Agent: ACB-MBA/5 CFNetwork/1333.0.4 Darwin/21.5.0',
            'Accept-Language: vi',
            'x-app-version: 3.7.0',
        ];

        $url = "https://apiapp.acb.com.vn/mb/legacy/ss/cs/bankservice/saving/tx-history?maxRows={$rows}&account={$accountNumber}";

        $result = $this->CURL2($url, $header, null);

        if (! isset($result['messageStatus']) || $result['messageStatus'] !== 'success') {
            $newToken = $this->refreshToken();

            if ($newToken) {
                $header[2] = "Authorization: bearer {$newToken}";
                $result = $this->CURL2($url, $header, null);
            }
        }

        return $this->JsonData('success', 'Lấy lịch sử giao dịch thành công.', $result);
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<string, mixed>|string|null  $data
     * @return array<string, mixed>
     */
    public function CURL2(string $Action, array $header, array|string|null $data): array
    {
        $Data = is_array($data) ? json_encode($data) : $data;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $Action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => ! empty($data),
            CURLOPT_POSTFIELDS => $Data,
            CURLOPT_CUSTOMREQUEST => empty($data) ? 'GET' : 'POST',
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2,
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($curl);
        curl_close($curl);

        return json_decode($body ?: '[]', true) ?: [];
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<string, mixed>|string|null  $data
     * @return array<string, mixed>
     */
    private function CURL(string $Action, array $header, array|string|null $data): array
    {
        $Data = is_array($data) ? json_encode($data) : $data;

        $curl = curl_init();

        $header[] = 'Content-Type: application/json; charset=utf-8';
        $header[] = 'accept: application/json';

        if ($Data) {
            $header[] = 'Content-Length: '.strlen($Data);
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->URL[$Action],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => ! empty($data),
            CURLOPT_POSTFIELDS => $Data,
            CURLOPT_CUSTOMREQUEST => empty($data) ? 'GET' : 'POST',
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($curl);
        curl_close($curl);

        return json_decode($body ?: '[]', true) ?: [];
    }

    public function generateImei(): string
    {
        return $this->generateRandomString(8).'-'
            .$this->generateRandomString(4).'-'
            .$this->generateRandomString(4).'-'
            .$this->generateRandomString(4).'-'
            .$this->generateRandomString(12);
    }

    public function generateRandomString(int $length = 20): string
    {
        $characters = '0123456789zxcvbnmlkjhgfdsaqwertyuiopZXCVBNMLKJHGFDSAQWERTYUIOP';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function get_TOKEN(): string
    {
        return $this->generateRandomString(39);
    }

    /**
     * @return array<string, mixed>
     */
    private function getLoginData(): array
    {
        if (! $this->bank instanceof BankAccount) {
            return [];
        }

        return is_array($this->bank->data_login) ? $this->bank->data_login : [];
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $overrides
     */
    private function persistLoginData(array $response, array $overrides = []): void
    {
        if (! $this->bank instanceof BankAccount) {
            return;
        }

        $loginData = array_merge($this->getLoginData(), [
            'provider' => 'acb',
            'access_token' => $response['accessToken'] ?? $this->getAccessToken(),
            'refresh_token' => $response['refreshToken'] ?? $this->getRefreshTokenValue(),
            'token_expired_at' => now()->addSeconds(max(((int) ($response['expiresIn'] ?? 0)) - 20, 0))->toDateTimeString(),
            'expires_in' => $response['expiresIn'] ?? null,
            'identity' => $response['identity'] ?? null,
        ], $overrides);

        $this->bank->forceFill([
            'account_name' => $this->resolveAccountName($response),
            'token' => $loginData['access_token'] ?? null,
            'data_login' => $loginData,
        ])->save();

        $this->bank->refresh();
    }

    private function getAccessToken(): string|false
    {
        $accessToken = $this->getLoginData()['access_token'] ?? $this->bank?->token;

        return is_string($accessToken) && $accessToken !== '' ? $accessToken : false;
    }

    private function getRefreshTokenValue(): string|false
    {
        $refreshToken = $this->getLoginData()['refresh_token'] ?? null;

        return is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : false;
    }

    private function getTokenExpiredAt(): ?Carbon
    {
        $tokenExpiredAt = $this->getLoginData()['token_expired_at'] ?? null;

        if (! is_string($tokenExpiredAt) || $tokenExpiredAt === '') {
            return null;
        }

        return Carbon::parse($tokenExpiredAt);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function resolveAccountName(array $response): ?string
    {
        $givenName = data_get($response, 'identity.name.givenName');
        $familyName = data_get($response, 'identity.name.familyName');

        $accountName = trim(implode(' ', array_filter([$givenName, $familyName])));

        if ($accountName !== '') {
            return $accountName;
        }

        return $this->bank?->account_name;
    }

    private static function jsonData($status, $message, $data = [])
    {
        return [
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }
}
