<?php

namespace App\Service\ApiBank;

use Throwable;

date_default_timezone_set('Asia/Ho_Chi_Minh');
class MbBank
{
    public $user = '';
    public $pass = '';
    public $deviceIdCommon_goc = '';
    public $URL = [];

    private $apiCaptcha = "http://103.118.29.97:8277";
    private $encodeParam = "http://103.118.29.97:2000";

    public function __construct($username, $password)
    {
        $this->user = trim((string) $username);
        $this->pass = trim((string) $password);
        $this->deviceIdCommon_goc = self::fakeDeviceId();
    }

    /**
     * @return array{success: bool, message: string, session?: array<string, string>, data?: array<string, mixed>}
     */
    public function doLogin(): array
    {
        $captcha = $this->eCaptcha();
        if (! is_string($captcha) || trim($captcha) === '' || str_starts_with($captcha, 'Error:')) {
            return [
                'success' => false,
                'message' => 'Không giải được captcha MB.',
                'data' => [
                    'captcha' => $captcha,
                ],
            ];
        }
        // dd($captcha);

        $response = $this->login(trim($captcha));
        if (! is_array($response)) {
            return [
                'success' => false,
                'message' => 'Không nhận được phản hồi hợp lệ từ MB.',
                'data' => [],
            ];
        }

        $responseCode = (string) $this->pickFirst($response, [
            'result.responseCode',
            'responseCode',
            'code',
            'statusCode',
        ], '');
        $message = (string) $this->pickFirst($response, [
            'result.message',
            'message',
            'des',
            'description',
        ], '');

        $sessionId = (string) $this->pickFirst($response, [
            'sessionId',
            'result.sessionId',
            'result.data.sessionId',
            'data.sessionId',
        ], '');
        $deviceId = (string) $this->pickFirst($response, [
            'deviceIdCommon',
            'result.deviceIdCommon',
            'data.deviceIdCommon',
        ], $this->deviceIdCommon_goc);

        $isSuccess = in_array($responseCode, ['00', '0', 'SUCCESS', 'success'], true) || $sessionId !== '';

        return [
            'success' => $isSuccess,
            'message' => $isSuccess ? 'success' : ($message !== '' ? $message : 'Đăng nhập MB thất bại.'),
            'session' => [
                'sessionId' => $sessionId,
                'deviceIdCommon' => $deviceId,
            ],
            'data' => $response,
        ];
    }

    /**
     * @return array{
     *   status: string,
     *   message: string,
     *   data: array{transactions: list<array<string, mixed>>}
     * }
     */
    public function getTransactionHistory(string $accountNumber, int $maxResults = 20, int $days = 7): array
    {
        $login = $this->doLogin();
        if (($login['success'] ?? false) !== true) {
            return [
                'status' => 'error',
                'message' => (string) ($login['message'] ?? 'Đăng nhập MB thất bại.'),
                'data' => ['transactions' => []],
            ];
        }

        $sessionId = (string) data_get($login, 'session.sessionId', '');
        $deviceId = (string) data_get($login, 'session.deviceIdCommon', $this->deviceIdCommon_goc);
        if ($sessionId === '') {
            return [
                'status' => 'error',
                'message' => 'Không lấy được session MB sau khi đăng nhập.',
                'data' => ['transactions' => []],
            ];
        }

        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');
        $rawHistory = $this->get_lsgd_1(
            $this->user,
            $sessionId,
            $deviceId,
            $accountNumber,
            $startDate,
            $endDate
        );

        $decodedHistory = json_decode((string) $rawHistory, true);
        if (! is_array($decodedHistory)) {
            return [
                'status' => 'error',
                'message' => 'Không parse được dữ liệu lịch sử giao dịch MB.',
                'data' => ['transactions' => []],
            ];
        }

        $rows = $this->extractTransactionRows($decodedHistory);
        $normalized = array_values(array_filter(array_map(
            fn(array $row): array => $this->normalizeTransaction($row),
            $rows
        ), fn(array $transaction): bool => ($transaction['transaction_id'] ?? '') !== ''));

        return [
            'status' => 'success',
            'message' => 'Lấy giao dịch MB thành công.',
            'data' => [
                'transactions' => array_slice($normalized, 0, max($maxResults, 1)),
            ],
        ];
    }

    public static function fakeDeviceId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    protected function extractTransactionRows(array $payload): array
    {
        $candidateKeys = [
            'transactionHistoryList',
            'transactions',
            'result.transactionHistoryList',
            'result.transactions',
            'data.transactionHistoryList',
            'data.transactions',
            'items',
            'list',
        ];

        foreach ($candidateKeys as $key) {
            $candidate = data_get($payload, $key);
            if (is_array($candidate) && array_is_list($candidate)) {
                return array_values(array_filter($candidate, fn(mixed $row): bool => is_array($row)));
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $rows = $this->extractTransactionRows($value);
                if ($rows !== []) {
                    return $rows;
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeTransaction(array $row): array
    {
        $description = (string) $this->pickFirst($row, [
            'description',
            'remark',
            'narrative',
            'content',
        ], '');

        $amount = (float) $this->pickFirst($row, [
            'amount',
            'transactionAmount',
        ], 0);

        $creditAmount = $this->toFloat((string) $this->pickFirst($row, ['creditAmount'], '0'));
        $debitAmount = $this->toFloat((string) $this->pickFirst($row, ['debitAmount'], '0'));
        $netAmount = $creditAmount - $debitAmount;

        $type = null;
        $creditFlag = strtolower((string) $this->pickFirst($row, ['creditDebitIndicator', 'type'], ''));
        if ($netAmount > 0) {
            $type = 'credit';
            $amount = $netAmount;
        } elseif ($netAmount < 0) {
            $type = 'debit';
            $amount = abs($netAmount);
        } elseif (in_array($creditFlag, ['credit', 'c', '+', 'in'], true)) {
            $type = 'credit';
        } elseif (in_array($creditFlag, ['debit', 'd', '-', 'out'], true)) {
            $type = 'debit';
        }

        if ($amount === 0.0) {
            $amount = $this->toFloat((string) $this->pickFirst($row, [
                'amount',
                'transactionAmount',
                'creditAmount',
                'debitAmount',
            ], '0'));
        }

        $transactionId = (string) $this->pickFirst($row, [
            'transactionId',
            'transaction_id',
            'refNo',
            'reference',
            'id',
        ], '');

        $transactionTimeRaw = (string) $this->pickFirst($row, [
            'postingDateTime',
            'transactionDateTime',
            'transactionDate',
            'postingDate',
            'bookingDate',
            'date',
        ], '');

        $transactionTime = $this->parseTransactionTime($transactionTimeRaw);

        if ($transactionId === '') {
            $transactionId = hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return [
            'transaction_id' => $transactionId,
            'amount' => abs($amount),
            'description' => $description,
            'transaction_time' => $transactionTime,
            'type' => $type,
            'raw_data' => $row,
        ];
    }

    protected function parseTransactionTime(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            \DateTimeInterface::ATOM,
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        try {
            return (new \DateTime($value))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $paths
     */
    protected function pickFirst(array $source, array $paths, mixed $default = null): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($source, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    protected function toFloat(string $value): float
    {
        $normalized = str_replace([',', ' '], '', $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
    public function eCaptcha($img_base64 = null)
    {
        if (!$img_base64) {
            $get_captcha = $this->get_captcha();
            $captchaPayload = json_decode((string) $get_captcha, true);
            $img_base64 = is_array($captchaPayload) ? ($captchaPayload['imageString'] ?? null) : null;

            if (! is_string($img_base64) || trim($img_base64) === '') {
                return 'Error: captcha payload is invalid';
            }
        }

        $captcha_domain = rtrim($this->apiCaptcha, '/');
        $url = $captcha_domain . '/api/captcha/mbbank';
        $dataPost = array(
            "base64" => $img_base64
        );
        $payload = json_encode($dataPost);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] == 'success') {
                return $result['captcha'];
            }
            return "Error: " . $response;
        }
    }

    private function getTimeNow()
    {
        return round(microtime(true) * 1000);
    }
    public function login($captcha)
    {

        $requestData = [
            'userId' => $this->user,
            'password' => md5($this->pass),
            'captcha' => $captcha,
            'ibAuthen2faString' => '',
            'sessionId' => null,
            'refNo' => $this->getTimeNow(),
            'deviceIdCommon' => $this->deviceIdCommon_goc,
        ];
        $encryptedPayload = $this->wasmEnc($requestData);
        $encrypt = is_array($encryptedPayload) ? ($encryptedPayload['dataEnc'] ?? null) : null;

        if (! is_string($encrypt) || trim($encrypt) === '') {
            return [
                'responseCode' => 'ERROR',
                'message' => 'Khong ma hoa duoc du lieu dang nhap MB.',
                'data' => $encryptedPayload,
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://online.mbbank.com.vn/api/retail_web/internetbanking/v2.0/doLogin',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"dataEnc":"' . $encrypt . '"}',
            CURLOPT_HTTPHEADER => array(
                'accept: application/json, text/plain, */*',
                'accept-language: vi,en-US;q=0.9,en;q=0.8,fr-FR;q=0.7,fr;q=0.6',
                'app: MB_WEB',
                'authorization: Basic RU1CUkVUQUlMV0VCOlNEMjM0ZGZnMzQlI0BGR0AzNHNmc2RmNDU4NDNm',
                'content-type: application/json; charset=UTF-8',
                'deviceid: o07leblt-mbib-0000-0000-2025111412160739',
                'elastic-apm-traceparent: 00-f1d5a073af8d289c7bf2bf0978773a42-43cfefd19479ab4a-01',
                'origin: https://online.mbbank.com.vn',
                'priority: u=1, i',
                'referer: https://online.mbbank.com.vn/pl/login?returnUrl=%2F',
                'refno: gfdgd-2025111607551958-53856',
                'sec-ch-ua: "Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-origin',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);


        return json_decode($response, true);
    }

    public function encryptData(array $data): array
    {
        $requiredFields = ['username', 'password', 'captcha', 'deviceId'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return [
                    'success' => false,
                    'message' => "Missing required field: {$field}",
                ];
            }
        }

        $baseUrl = rtrim((string) $this->encodeParam, '/');
        $query = http_build_query([
            'username' => (string) $data['username'],
            'password' => (string) $data['password'],
            'captcha' => (string) $data['captcha'],
            'deviceId' => (string) $data['deviceId'],
        ]);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $baseUrl . '/encode?' . $query,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error #: ' . $error,
            ];
        }

        if ($response === false || $response === '') {
            return [
                'success' => false,
                'message' => 'Empty response from encode service',
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode((string) $response, true);

        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Invalid encrypt response',
                'http_code' => $httpCode,
                'raw_response' => $response,
            ];
        }

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'message' => $decoded['message'] ?? 'Encode service error',
                'http_code' => $httpCode,
                'response' => $decoded,
            ];
        }

        return $decoded;
    }

    public function wasmEnc($data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://103.78.2.65:7222/mb/encrypt-data',//http://103.78.2.65:7222/mb/encrypt-data
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return [
                'message' => 'cURL Error #:' . $error,
            ];
        }

        $decoded = json_decode((string) $response, true);

        if (! is_array($decoded)) {
            return [
                'message' => 'Invalid encrypt response',
                'raw_response' => $response,
            ];
        }

        return $decoded;
    }
    public function get_captcha()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://online.mbbank.com.vn/api/retail-internetbankingms/getCaptchaImage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"refNo":"2025111607522690","deviceIdCommon":"' . $this->deviceIdCommon_goc . '","sessionId":""}',
            CURLOPT_HTTPHEADER => array(
                'accept: application/json, text/plain, */*',
                'accept-language: vi,en-US;q=0.9,en;q=0.8,fr-FR;q=0.7,fr;q=0.6',
                'app: MB_WEB',
                'authorization: Basic RU1CUkVUQUlMV0VCOlNEMjM0ZGZnMzQlI0BGR0AzNHNmc2RmNDU4NDNm',
                'content-type: application/json; charset=UTF-8',
                'deviceid: o07leblt-mbib-0000-0000-2025111412160739',
                'elastic-apm-traceparent: 00-d0c083694431b8bd1b3e5d9871c418bf-9ba19b4c13a66602-01',
                'origin: https://online.mbbank.com.vn',
                'priority: u=1, i',
                'referer: https://online.mbbank.com.vn/pl/login?returnUrl=%2F',
                'refno: 2025111607522690',
                'sec-ch-ua: "Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-origin',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    public function get_lsgd($user, $session_id, $deviceId, $account, $day)
    {
        $today = new \DateTime();
        $today->modify("+1 day");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://online.mbbank.com.vn/api/retail-transactionms/transactionms/get-account-transaction-history',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "toDate" : "' . date("d/m/Y") . '",
            "accountNo" : "' . $account . '",
            "sessionId" : "' . $session_id . '",
            "fromDate" : "' . date("d/m/Y", strtotime("$day days ago")) . '",
            "refNo" : "' . $user . '-' . date('YmdHis') . '",
            "deviceIdCommon" : "' . $deviceId . '"
          }',
            CURLOPT_HTTPHEADER => array(
                'Deviceid: ' . $deviceId . '',
                'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'sec-ch-ua-mobile: ?0',
                'Authorization: Basic RU1CUkVUQUlMV0VCOlNEMjM0ZGZnMzQlI0BGR0AzNHNmc2RmNDU4NDNm',
                'elastic-apm-traceparent: 00-690e238f5a479be690001e5257478972-4b8184bf0f444db1-01',
                'Content-Type: application/json; charset=UTF-8',
                'RefNo: 0978364572-2024011200141777',
                'Accept: application/json, text/plain, */*',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer: https://online.mbbank.com.vn/information-account/source-account',
                'X-Request-Id: ' . $user . '-' . date('YmdHis') . '',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    public function get_lsgd_1($user, $session_id, $deviceId, $account, $start_date, $end_date)
    {
        $start_date = date("d/m/Y", strtotime($start_date));
        $end_date = date("d/m/Y", strtotime($end_date));
        $refNo = $user . '-' . date('YmdHis');

        // dd([
        //     "toDate" => $end_date,
        //     "accountNo" => $account,
        //     "sessionId" => $session_id,
        //     "fromDate" => $start_date,
        //     "refNo" => $refNo,
        //     "deviceIdCommon" => $deviceId
        // ]);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://online.mbbank.com.vn/api/retail-transactionms/transactionms/get-account-transaction-history',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "toDate" => $end_date,
                "accountNo" => $account,
                "sessionId" => $session_id,
                "fromDate" => $start_date,
                "refNo" => $refNo,
                "deviceIdCommon" => $deviceId
            ]),
            CURLOPT_HTTPHEADER => array(
                'accept: application/json, text/plain, */*',
                'accept-language: vi,en-US;q=0.9,en;q=0.8,fr-FR;q=0.7,fr;q=0.6',
                'app: MB_WEB',
                'authorization: Basic RU1CUkVUQUlMV0VCOlNEMjM0ZGZnMzQlI0BGR0AzNHNmc2RmNDU4NDNm',
                'content-type: application/json; charset=UTF-8',
                'deviceid: ' . $deviceId,
                'elastic-apm-traceparent: 00-690e238f5a479be690001e5257478972-4b8184bf0f444db1-01',
                'origin: https://online.mbbank.com.vn',
                'priority: u=1, i',
                'referer: https://online.mbbank.com.vn/information-account/source-account',
                'refNo: ' . $refNo,
                'sec-ch-ua: "Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-origin',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'
            ),
        ));

        $response = curl_exec($curl);
        // dd($response);
        curl_close($curl);

        return $response;
    }
    public function get_lsgd_2($user, $session_id, $deviceId, $account, $day)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $today = new \DateTime();
        $today->modify("+1 day");

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://online.mbbank.com.vn/api/retail-transactionms/transactionms/get-account-transaction-history',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "toDate" : "' . date("d/m/Y") . '",
            "accountNo" : "' . $account . '",
            "sessionId" : "' . $session_id . '",
            "fromDate" : "' . date("d/m/Y", strtotime("$day days ago")) . '",
            "refNo" : "' . $user . '-' . date('YmdHis') . '",
            "deviceIdCommon" : "' . $deviceId . '"
          }',
            CURLOPT_HTTPHEADER => array(
                'accept: application/json, text/plain, */*',
                'accept-language: vi,en-US;q=0.9,en;q=0.8,fr-FR;q=0.7,fr;q=0.6',
                'app: MB_WEB',
                'authorization: Basic RU1CUkVUQUlMV0VCOlNEMjM0ZGZnMzQlI0BGR0AzNHNmc2RmNDU4NDNm',
                'content-type: application/json; charset=UTF-8',
                'deviceid: o07leblt-mbib-0000-0000-2025111412160739',
                'elastic-apm-traceparent: 00-f5f58466be15fa772875a9b5445cdfdc-66e26d2a1eb6130a-01',
                'origin: https://online.mbbank.com.vn',
                'priority: u=1, i',
                'referer: https://online.mbbank.com.vn/information-account/source-account',
                'refno: 0397333616-2025111608014806-75874',
                'sec-ch-ua: "Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-origin',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    public function get_balance($session_id, $deviceId)
    {
        $refNo = $this->generateRefNo2($this->user);
        $header = array(
            'accept: application/json, text/plain, */*',
            'accept-language: vi-VN,vi;q=0.9,fr-FR;q=0.8,fr;q=0.7,en-US;q=0.6,en;q=0.5',
            'app: MB_WEB',
            'authorization: Basic RU1CUkVUQUlMV0VCOlNEMjM0ZGZnMzQlI0BGR0AzNHNmc2RmNDU4NDNm',
            'content-type: application/json; charset=UTF-8',
            'deviceid: ' . $deviceId,
            'elastic-apm-traceparent: 00-2297f87ef45beb4234545809143e2fbe-1d26e459b80cfe1d-01',
            'origin: https://online.mbbank.com.vn',
            'priority: u=1, i',
            'referer: https://online.mbbank.com.vn/',
            'refno: ' . $refNo,
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1',
            'x-request-id: ' . $refNo,
        );
        $Action = 'https://online.mbbank.com.vn/api/retail-accountms/accountms/getBalance';
        $Data = json_encode([
            "sessionId" => $session_id,
            "refNo" => $refNo,
            "deviceIdCommon" => $deviceId
        ]);
        $result = $this->CURL2($Action, $header, $Data);
        return $result;
    }
    public function CURL2($Action, $header, $data)
    {
        $curl = curl_init();
        $opt = array(
            CURLOPT_URL => $Action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => empty($data) ? false : true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_CUSTOMREQUEST => empty($data) ? 'GET' : 'POST',
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_ENCODING => "",
            CURLOPT_HEADER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2,
            CURLOPT_TIMEOUT => 5,
        );
        curl_setopt_array($curl, $opt);
        $body = curl_exec($curl);

        return $body;
    }
    public function generateRefNo2($phoneNumber)
    {
        // Part 1: Fixed phone number
        $part1 = $phoneNumber;

        // Part 2: Current timestamp with milliseconds (YYYYMMDDHHMMSSMS)
        $dateTime = new \DateTime();
        $timestamp = $dateTime->format('YmdHis') . substr($dateTime->format('u'), 0, 2); // YearMonthDayHourMinuteSecond + 2-digit milliseconds

        // Part 3: Random 5-digit number (e.g., 00000 to 99999)
        $part3 = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

        // Combine all parts with hyphens
        $refNo = "$part1-$timestamp-$part3";

        return $refNo;
    }

    public function generateImei()
    {
        return $this->generateRandomString(8) . '-' . $this->generateRandomString(4) . '-' . $this->generateRandomString(4) . '-' . $this->generateRandomString(4) . '-' . $this->get_time_request();
    }

    public function generateRandomString($length = 20)
    {
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public function get_TOKEN()
    {
        return $this->generateRandomString(39);
    }
    public function get_time_request()
    {
        $d = getdate();
        $today = $d['hours'] . $d['minutes'] . $d['seconds'];
        $day = date('Y') . date('m') . date('d');
        return $day . $today;
    }
}
