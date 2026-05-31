<?php

namespace App\Service\ApiBank;

use App\Models\BankAccount;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class vietCombank
{
    protected $captcha1st = '916c00d9131f402897dd6af51e0147dc';

    protected $captchaApiKey = '916c00d9131f402897dd6af51e0147dc';

    protected $haicaptcha = '490fd597370afaf016cf3f0437da98b5';

    protected $defaultPublicKey = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAikqQrIzZJkUvHisjfu5ZCN+TLy//43CIc5hJE709TIK3HbcC9vuc2+PPEtI6peSUGqOnFoYOwl3i8rRdSaK17G2RZN01MIqRIJ/6ac9H4L11dtfQtR7KHqF7KD0fj6vU4kb5+0cwR3RumBvDeMlBOaYEpKwuEY9EGqy9bcb5EhNGbxxNfbUaogutVwG5C1eKYItzaYd6tao3gq7swNH7p6UdltrCpxSwFEvc7douE2sKrPDp807ZG2dFslKxxmR4WHDHWfH0OpzrB5KKWQNyzXxTBXelqrWZECLRypNq7P+1CyfgTSdQ35fdO7M1MniSBT1V33LdhXo73/9qD5e5VQIDAQAB\n-----END PUBLIC KEY-----";

    protected $clientPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCg+aN5HEhfrHXCI/pLcv2Mg01gNzuAlqNhL8ojO8KwzrnEIEuqmrobjMFFPkrMXUnmY5cWsm0jxaflAtoqTf9dy1+LL5ddqNOvaPsNhSEMmIUsrppvh1ZbUZGGW6OUNeXBEDXhEF8tAjl3KuBiQFLEECUmCDiusnFoZ2w/1iOZJwIDAQAB';

    protected $clientPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\r\nMIICXQIBAAKBgQCg+aN5HEhfrHXCI/pLcv2Mg01gNzuAlqNhL8ojO8KwzrnEIEuq\r\nmrobjMFFPkrMXUnmY5cWsm0jxaflAtoqTf9dy1+LL5ddqNOvaPsNhSEMmIUsrppv\r\nh1ZbUZGGW6OUNeXBEDXhEF8tAjl3KuBiQFLEECUmCDiusnFoZ2w/1iOZJwIDAQAB\r\nAoGAEGDV7SCfjHxzjskyUjLk8UL6wGteNnsdLGo8WtFdwbeG1xmiGT2c6eisUWtB\r\nGQH03ugLG1gUGqulpXtgzyUYcj0spHPiUiPDAPY24DleR7lGZHMfsnu20dyu6Llp\r\nXup07OZdlqDGUm9u2uC0/I8RET0XWCbtOSr4VgdHFpMN+MECQQDbN5JOAIr+px7w\r\nuhBqOnWJbnL+VZjcq39XQ6zJQK01MWkbz0f9IKfMepMiYrldaOwYwVxoeb67uz/4\r\nfau4aCR5AkEAu/xLydU/dyUqTKV7owVDEtjFTTYIwLs7DmRe247207b6nJ3/kZhj\r\ngsm0mNnoAFYZJoNgCONUY/7CBHcvI4wCnwJBAIADmLViTcjd0QykqzdNghvKWu65\r\nD7Y1k/xiscEour0oaIfr6M8hxbt8DPX0jujEf7MJH6yHA+HfPEEhKila74kCQE/9\r\noIZG3pWlU+V/eSe6QntPkE01k+3m/c82+II2yGL4dpWUSb67eISbreRovOb/u/3+\r\nYywFB9DxA8AAsydOGYMCQQDYDDLAlytyG7EefQtDPRlGbFOOJrNRyQG+2KMEl/ti\r\nYr4ZPChxNrik1CFLxfkesoReXN8kU/8918D0GLNeVt/C\r\n-----END RSA PRIVATE KEY-----\r\n";

    protected $url = [
        'getCaptcha' => 'https://digiapp.vietcombank.com.vn/utility-service/v1/captcha/',
        'login' => 'https://digiapp.vietcombank.com.vn/authen-service/v1/login',
        'authen-service' => 'https://digiapp.vietcombank.com.vn/authen-service/v1/api-',
        'getHistories' => 'https://digiapp.vietcombank.com.vn/bank-service/v1/transaction-history',
        'tranferOut' => 'https://digiapp.vietcombank.com.vn/napas-service/v1/init-fast-transfer-via-accountno',
        'genOtpOut' => 'https://digiapp.vietcombank.com.vn/napas-service/v1/transfer-gen-otp',
        'genOtpIn' => 'https://digiapp.vietcombank.com.vn/transfer-service/v1/transfer-gen-otp',
        'confirmTranferOut' => 'https://digiapp.vietcombank.com.vn/napas-service/v1/transfer-confirm-otp',
        'confirmTranferIn' => 'https://digiapp.vietcombank.com.vn/transfer-service/v1/transfer-confirm-otp',
        'tranferIn' => 'https://digiapp.vietcombank.com.vn/transfer-service/v1/init-internal-transfer',
        'getBanks' => 'https://digiapp.vietcombank.com.vn/utility-service/v1/get-banks',
        'getAccountDeltail' => 'https://digiapp.vietcombank.com.vn/bank-service/v1/get-account-detail',
        'getlistAccount' => 'https://digiapp.vietcombank.com.vn/bank-service/v1/get-list-account-via-cif',
        'getlistDDAccount' => 'https://digiapp.vietcombank.com.vn/bank-service/v1/get-list-ddaccount',
    ];

    protected $lang = 'vi';

    protected $_timeout = 60;

    protected $DT = 'Windows';

    protected $OV = '10';

    protected $PM = 'Chrome 111.0.0.0';

    protected $checkAcctPkg = '1';

    protected $username;

    protected $password;

    protected $account_number;

    protected $captchaToken;

    protected $captchaValue;

    protected $proxy = '';

    // account
    protected $sessionId;

    protected $mobileId;

    protected $clientId;

    protected $cif;

    protected $res;

    protected $browserToken = '';

    protected $browserId = '';

    protected $E = '';

    protected $tranId = '';

    protected $token;

    protected $accessToken;

    protected $authToken;

    protected ?BankAccount $bankAccount = null;

    public function __construct($username, $password, $account_number, ?BankAccount $bankAccount = null)
    {
        $this->bankAccount = $bankAccount;

        if (! $this->bankAccount instanceof BankAccount) {
            $this->bankAccount = BankAccount::query()
                ->where('bank_name', 'vcb')
                ->where('username', $username)
                ->where('account_number', $account_number)
                ->latest('id')
                ->first();
        }

        if (! $this->bankAccount instanceof BankAccount) {
            $this->username = $username;
            $this->password = $password;
            $this->account_number = $account_number;
            $this->clientId = '';
            $this->browserId = md5($this->username);
            $this->saveData();
        } else {
            $this->parseData();

            if ((string) $password !== '' && (string) $this->password !== (string) $password) {
                $this->password = (string) $password;
                $this->bankAccount->password = (string) $password;
                $this->bankAccount->save();
                $this->bankAccount->refresh();
            }
        }
    }

    public function saveData()
    {
        $existing = $this->bankAccount;
        $resolvedAccountName = $this->resolveAccountName($existing);
        $data_login = [
            'sessionId' => $this->sessionId ?? '',
            'mobileId' => $this->mobileId ?? '',
            'clientId' => $this->clientId ?? '',
            'cif' => $this->cif ?? '',
            'E' => $this->E ?? '',
            'res' => json_encode($this->res) ?? '',
            // 'res'                   => null,
            'tranId' => $this->tranId ?? '',
            'browserToken' => $this->browserToken ?? '',
            'browserId' => $this->browserId ?? '',
        ];

        $data = [
            'bank_name' => 'vcb',
            'account_name' => $resolvedAccountName,
            'account_number' => $this->account_number ?? ($existing->account_number ?? ''),
            'username' => $this->username ?? ($existing->username ?? ''),
            'password' => $this->password ?? ($existing->password ?? ''),
            'token' => null,
            'data_login' => $data_login,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ];

        $bankAccount = $existing ?? new BankAccount;

        $bankAccount->forceFill($data);

        if (! $bankAccount->exists) {
            $bankAccount->created_at = Carbon::now()->toDateTimeString();
        }

        $bankAccount->save();

        $this->bankAccount = $bankAccount->fresh();
    }

    protected function resolveAccountName(?BankAccount $existing): string
    {
        $existingAccountName = isset($existing->account_name) ? trim((string) $existing->account_name) : '';

        if ($existingAccountName !== '') {
            return $existingAccountName;
        }

        $username = trim((string) ($this->username ?? ''));
        if ($username !== '') {
            return $username;
        }

        return trim((string) ($this->account_number ?? ''));
    }

    public function parseData()
    {
        $data = $this->bankAccount;

        if (! $data instanceof BankAccount) {
            return;
        }

        $this->username = $data->username ?? '';
        $this->password = $data->password ?? '';
        $this->account_number = $data->account_number ?? '';
        $this->token = $data->token ?? '';

        $dataLogin = is_array($data->data_login ?? null) ? $data->data_login : [];

        $this->sessionId = $dataLogin['sessionId'] ?? '';
        $this->mobileId = $dataLogin['mobileId'] ?? '';
        $this->clientId = $dataLogin['clientId'] ?? '';
        $this->cif = $dataLogin['cif'] ?? '';
        $this->res = $dataLogin['res'] ?? '';
        $this->tranId = $dataLogin['tranId'] ?? '';
        $this->browserToken = $dataLogin['browserToken'] ?? '';
        $this->browserId = $dataLogin['browserId'] ?? md5($this->username);
        $this->E = $dataLogin['E'] ?? '';
        // die(print_r($data));
    }

    protected function getE()
    {
        $ahash = md5($this->username);
        $imei = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($ahash, 4));

        return strtoupper($imei);
    }

    public function getCaptcha()
    {
        $this->captchaToken = Str::random(30);
        $url = 'https://digiapp.vietcombank.com.vn/utility-service/v1/captcha/'.$this->captchaToken;
        $client = new Client(['http_errors' => false]);
        $res = $client->request('GET', $url, [
            'timeout' => $this->_timeout,
            'proxy' => $this->proxy,
            'headers' => [
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36',
            ],
        ]);
        $result = $res->getBody()->getContents();

        return base64_encode($result);
    }

    private function createTask($image)
    {
        $client = new Client;
        try {
            $res = $client->request('POST', 'https://api.1stcaptcha.com/recognition', [
                'json' => [
                    'Apikey' => $this->captcha1st,
                    'Type' => 'imagetotext',
                    'Image' => $image,
                ],
            ]);

            return json_decode($res->getBody());
        } catch (\Throwable $th) {
        }

        return false;
    }

    private function createTask2captcha($image)
    {
        $client = new Client;
        try {
            $res = $client->request('POST', 'https://anticaptcha.top/api/captcha', [
                'json' => [
                    'apikey' => $this->haicaptcha,
                    'Type' => '9',
                    'img' => $image,
                ],
            ]);

            return json_decode($res->getBody());
        } catch (\Throwable $th) {
        }

        return false;
    }

    private function getTaskResult($taskId, $j = 0)
    {
        if ($j >= 5) {
            return ['status' => false];
        }
        $client = new Client;
        try {
            $res = $client->request('GET', "https://api.1stcaptcha.com/getresult?apikey={$this->captcha1st}&taskid=".$taskId);
            $result = json_decode($res->getBody());

            if ($result->Status == 'SUCCESS') {
                return ['status' => true, 'captcha' => $result->Data];
            } elseif ($result->status == 'processing') {
                sleep(3);
                $j++;

                return $this->getTaskResult($taskId, $j);
            }
        } catch (\Throwable $th) {
        }

        return ['status' => false];
    }

    public function bypass()
    {
        $base64 = $this->getCaptcha();
        // dd($base64);
        $data = json_encode([
            'base64' => $base64,   // ✅ sửa key
        ]);
        // telecode($base64, "-5237556794");
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://captcha-vcb.apibankvn.com/api/captcha/vcb',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: '.strlen($data), // optional nhưng nên có
            ],
        ]);

        $response = curl_exec($curl);
        // telecode("bypass vcb => ".$response, "-5237556794");
        if (curl_errno($curl)) {
            return [
                'status' => 'error',
                'message' => curl_error($curl),
            ];
        }

        curl_close($curl);

        // decode luôn cho tiện dùng
        return $response;
    }

    public function solveCaptcha()
    {
        // return "ok";
        $result = json_decode($this->bypass()); // ảnh base 64
        // return $getCaptcha;
        if (! is_object($result) || ! isset($result->captcha) || empty($result->captcha)) {
            return ['status' => false, 'msg' => 'Không giải được captcha'];
        }
        $this->captchaValue = $result->captcha;

        // telecode("captcha => ".$result->captcha, "-5237556794");
        return ['status' => true, 'key' => $this->haicaptcha, 'captcha' => $result->captcha];

        // Legacy fallback block removed.

    }

    public function checkBrowser($type = 1)
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'lang' => $this->lang,
            'mid' => 3008,
            'cif' => '',
            'clientId' => '',
            'mobileId' => '',
            'sessionId' => '',
            'browserToken' => $this->browserToken,
            'user' => $this->username,
        ];
        $result = $this->curlPost($this->url['authen-service'].'3008', $param);
        if (isset($result->transaction->tranId)) {

            return $this->chooseOtpType($result->transaction->tranId, $type);
        } else {
            return [
                'success' => false,
                'message' => 'checkBrowser failed',
                'param' => $param,
                'data' => $result ?: '',
            ];
        }
    }

    public function chooseOtpType($tranID, $type = 1)
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'lang' => $this->lang,
            'mid' => 3010,
            'cif' => '',
            'clientId' => '',
            'mobileId' => '',
            'sessionId' => '',
            'browserToken' => $this->browserToken,
            'tranId' => $tranID,
            'type' => $type, // 1 la sms,5 la smart
            'user' => $this->username,
        ];
        $result = $this->curlPost($this->url['authen-service'].'3010', $param);
        if ($result->code == 00) {

            $this->tranId = $tranID;
            $this->saveData();

            return [
                'success' => true,
                'message' => 'ok',
                'result' => [
                    'browserToken' => $this->browserToken,
                    'tranId' => isset($result->tranId) ? $result->tranId : '',
                    'challenge' => isset($result->challenge) ? $result->challenge : '',
                ],
                'param' => $param,
                'data' => $result ?: '',
            ];
        }
    }

    public function submitOtpLogin($otp)
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'lang' => $this->lang,
            'mid' => 3011,
            'cif' => '',
            'clientId' => '',
            'mobileId' => '',
            'sessionId' => '',
            'browserToken' => $this->browserToken,
            'tranId' => $this->tranId,
            'otp' => $otp,
            'user' => $this->username,
        ];

        $result = $this->curlPost($this->url['authen-service'].'3011', $param);
        // return $result;
        if ($result->code == 00) {
            $this->sessionId = $result->sessionId;
            $this->mobileId = $result->userInfo->mobileId;
            $this->clientId = $result->userInfo->clientId;
            $this->cif = $result->userInfo->cif;
            $session = ['sessionId' => $this->sessionId, 'mobileId' => $this->mobileId, 'clientId' => $this->clientId, 'cif' => $this->cif];
            $this->res = $result;
            $this->saveData();
            $sv = $this->saveBrowser();
            if ($sv->code == 00) {
                return [
                    'success' => true,
                    'message' => 'success',
                    'd' => $sv,
                    'session' => $session,
                    'data' => $result ?: '',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $sv->des,
                    'param' => $param,
                    'data' => $sv ?: '',
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => $result->des,
                'param' => $param,
                'data' => $result ?: '',
            ];
        }
    }

    public function saveBrowser()
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => '',
            'browserId' => $this->browserId,
            'browserName' => 'Chrome 111.0.0.0',
            'lang' => $this->lang,
            'mid' => 3009,
            'cif' => $this->cif,
            'clientId' => $this->clientId,
            'mobileId' => $this->mobileId,
            'sessionId' => $this->sessionId,
            'user' => $this->username,
        ];
        $result = $this->curlPost($this->url['authen-service'].'3009', $param);

        return $result;
    }

    public function doLogin()
    {
        $solveCaptcha = $this->solveCaptcha();
        // return $solveCaptcha;
        if ($solveCaptcha['status'] == false) {
            return $solveCaptcha;
        }

        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'captchaToken' => $this->captchaToken,
            'captchaValue' => $this->captchaValue,
            'checkAcctPkg' => $this->checkAcctPkg,
            'lang' => $this->lang,
            'mid' => 6,
            'password' => $this->password,
            'user' => $this->username,
        ];
        $result = $this->curlPost($this->url['login'], $param);
        if (! is_object($result) || ! isset($result->code)) {
            return [
                'success' => false,
                'message' => 'Không nhận được phản hồi hợp lệ từ VCB khi đăng nhập.',
                'param' => $param,
                'data' => $result,
            ];
        }
        if ($result->code == 00) {
            $this->sessionId = $result->sessionId;
            $this->mobileId = $result->userInfo->mobileId;
            $this->clientId = $result->userInfo->clientId;
            $this->cif = $result->userInfo->cif;
            $session = ['sessionId' => $this->sessionId, 'mobileId' => $this->mobileId, 'clientId' => $this->clientId, 'cif' => $this->cif];
            $this->saveData();

            return [
                'success' => true,
                'message' => 'success',
                'session' => $session,
                'data' => $result ?: '',
            ];
        } elseif ($result->code == 20231 && $result->mid == 6) {
            $this->browserToken = $result->browserToken;

            return $this->checkBrowser(1); // 5 la smart otp
        } else {
            return [
                'success' => false,
                'message' => $result->des,
                'param' => $param,
                'data' => $result ?: '',
            ];
        }
    }

    public function setData($sessionId, $mobileId, $clientId, $cif)
    {
        $this->sessionId = $sessionId;
        $this->mobileId = $mobileId;
        $this->clientId = $clientId;
        $this->cif = $cif;

        return $this;
    }

    public function getlistAccount()
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'browserId' => $this->browserId,
            'E' => $this->getE() ?: '',
            'mid' => 8,
            'cif' => $this->cif,
            'user' => $this->username,
            'mobileId' => $this->mobileId,
            'clientId' => $this->clientId,
            'sessionId' => $this->sessionId,
        ];
        $result = $this->curlPost($this->url['getlistAccount'], $param);

        return $result;
    }

    public function getlistDDAccount()
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'browserId' => $this->browserId,
            'E' => $this->getE() ?: '',
            'mid' => 35,
            'cif' => $this->cif,
            'serviceCode' => '0551',
            'user' => $this->username,
            'mobileId' => $this->mobileId,
            'clientId' => $this->clientId,
            'sessionId' => $this->sessionId,
        ];
        $result = $this->curlPost($this->url['getlistDDAccount'], $param);

        return $result;
    }

    public function getAccountDeltail()
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'accountNo' => $this->account_number,
            'accountType' => 'D',
            'mid' => 13,
            'cif' => $this->cif,
            'user' => $this->username,
            'mobileId' => $this->mobileId,
            'clientId' => $this->clientId,
            'sessionId' => $this->sessionId,
        ];
        $result = $this->curlPost($this->url['getAccountDeltail'], $param);

        return $result;
    }

    public function getHistories($fromDate = '16/06/2023', $toDate = '16/06/2023', $account_number = '', $page = 0)
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'accountNo' => $account_number ? $account_number : $this->account_number,
            'accountType' => 'D',
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'lang' => $this->lang,
            'pageIndex' => $page,
            'lengthInPage' => 20,
            'stmtDate' => '',
            'stmtType' => '',
            'mid' => 14,
            'cif' => $this->cif,
            'user' => $this->username,
            'mobileId' => $this->mobileId,
            'clientId' => $this->clientId,
            'sessionId' => $this->sessionId,
        ];
        $result = $this->curlPost($this->url['getHistories'], $param);

        return $result;
    }

    /**
     * Chuẩn hóa số tiền: "25,000" => 25000.0
     */
    protected function normalizeAmount($amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        if (! is_string($amount)) {
            return 0.0;
        }

        $normalized = str_replace([',', ' '], '', $amount);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    /**
     * Chuẩn hóa type giao dịch về credit/debit theo DorCCode hoặc CD.
     */
    protected function normalizeType(array $transaction): ?string
    {
        $dorCCode = strtoupper(trim((string) ($transaction['DorCCode'] ?? '')));
        if ($dorCCode === 'C') {
            return 'credit';
        }
        if ($dorCCode === 'D') {
            return 'debit';
        }

        $cd = trim((string) ($transaction['CD'] ?? ''));
        if ($cd === '+') {
            return 'credit';
        }
        if ($cd === '-') {
            return 'debit';
        }

        return null;
    }

    /**
     * Chuẩn hóa thời gian giao dịch về Y-m-d H:i:s.
     */
    protected function normalizeTransactionTime(array $transaction): ?string
    {
        $postingDate = trim((string) ($transaction['PostingDate'] ?? ''));
        $postingTime = preg_replace('/[^0-9]/', '', (string) ($transaction['PostingTime'] ?? ''));

        if ($postingDate !== '' && strlen($postingTime) >= 6) {
            $time = substr($postingTime, 0, 2).':'.substr($postingTime, 2, 2).':'.substr($postingTime, 4, 2);
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$postingDate} {$time}");
            if ($dateTime instanceof \DateTime) {
                return $dateTime->format('Y-m-d H:i:s');
            }
        }

        $tranDate = trim((string) ($transaction['tranDate'] ?? ($transaction['TransactionDate'] ?? '')));
        $pcTime = preg_replace('/[^0-9]/', '', (string) ($transaction['PCTime'] ?? ''));
        if ($tranDate !== '' && strlen($pcTime) >= 6) {
            $time = substr($pcTime, 0, 2).':'.substr($pcTime, 2, 2).':'.substr($pcTime, 4, 2);
            $dateTime = \DateTime::createFromFormat('d/m/Y H:i:s', "{$tranDate} {$time}");
            if ($dateTime instanceof \DateTime) {
                return $dateTime->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /**
     * Chuẩn hóa 1 transaction theo format chung của hệ thống.
     */
    protected function normalizeTransactionRecord(array $transaction): array
    {
        $reference = trim((string) ($transaction['Reference'] ?? ''));
        $seqNo = trim((string) ($transaction['SeqNo'] ?? ''));
        $description = trim((string) ($transaction['Description'] ?? ($transaction['Remark'] ?? '')));

        $transactionId = trim($seqNo !== '' ? $seqNo : $reference);
        if ($transactionId === '') {
            $transactionId = hash('sha256', json_encode($transaction));
        }

        return [
            'transaction_id' => $transactionId,
            'reference' => $reference,
            'seq_no' => $seqNo,
            'amount' => $this->normalizeAmount($transaction['Amount'] ?? 0),
            'description' => $description,
            'transaction_time' => $this->normalizeTransactionTime($transaction),
            'type' => $this->normalizeType($transaction),
            'raw_data' => $transaction,
        ];
    }

    public function getTransactionHistory($fromDate, $toDate, $account_number, $maxResults = 100)
    {
        $result = [];
        $page = 0;
        $hasMoreResults = true;
        $mid = null;
        $code = null;
        $des = null;
        $clientIp = null;

        while (count($result) < $maxResults && $hasMoreResults) {
            $history = $this->getHistories($fromDate, $toDate, $account_number, $page);
            // return $history;
            if (! is_object($history)) {
                $hasMoreResults = false;
                break;
            }

            $mid = $history->mid ?? null;
            $code = $history->code ?? null;
            $des = $history->des ?? null;
            $clientIp = $history->clientIp ?? null;

            $historyCode = (string) ($history->code ?? '');
            if (! in_array($historyCode, ['00', '0'], true) || strtolower((string) ($history->des ?? '')) !== 'success') {
                $hasMoreResults = false;
                break;
            }

            $transactions = is_array($history->transactions ?? null) ? $history->transactions : [];
            $result = array_merge($result, $transactions);

            if (($history->nextIndex ?? -1) == -1 || $transactions === []) {
                $hasMoreResults = false;
            }
            $page++;
        }

        $transactions = array_slice($result, 0, $maxResults);
        $normalizedTransactions = array_map(function ($transaction) {
            $item = (array) $transaction;

            return $this->normalizeTransactionRecord($item);
        }, $transactions);

        $arr = json_encode([
            'mid' => $mid,
            'code' => $code,
            'des' => $des,
            'clientIp' => $clientIp,
            'transactions' => $transactions,
            'normalized_transactions' => $normalizedTransactions,
        ]);

        return json_decode($arr);
    }

    public function getBanks()
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'lang' => $this->lang,
            'fastTransfer' => '1',
            'mid' => 23,
            'cif' => $this->cif,
            'user' => $this->username,
            'mobileId' => $this->mobileId,
            'clientId' => $this->clientId,
            'sessionId' => $this->sessionId,
        ];
        $result = $this->curlPost($this->url['getBanks'], $param);

        return $result;
    }

    public function createTranferOutVietCombank($bankCode, $account_number, $amount, $message)
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'lang' => $this->lang,
            'debitAccountNo' => $this->account_number,
            'creditAccountNo' => $account_number,
            'creditBankCode' => $bankCode,
            'amount' => $amount,
            'feeType' => 1,
            'content' => $message,
            'ccyType' => '1',
            'mid' => 62,
            'cif' => $this->cif,
            'user' => $this->username,
            'mobileId' => $this->mobileId,
            'clientId' => $this->clientId,
            'sessionId' => $this->sessionId,
        ];
        $result = $this->curlPost($this->url['tranferOut'], $param);

        return $result;
    }

    public function createTranferInVietCombank($account_number, $amount, $message)
    {
        $param = [
            'DT' => $this->DT,
            'OV' => $this->OV,
            'PM' => $this->PM,
            'E' => $this->getE() ?: '',
            'browserId' => $this->browserId,
            'lang' => $this->lang,
            'debitAccountNo' => $this->account_number,
            'creditAccountNo' => $account_number,
            'amount' => $amount,
            'activeTouch' => 0,
            'feeType' => 1,
            'content' => $message,
            'ccyType' => '',
            'mid' => 16,
            'cif' => $this->cif,
            'user' => $this->username,
            'mobileId' => $this->mobileId,
            'clientId' => $this->clientId,
            'sessionId' => $this->sessionId,
        ];
        $result = $this->curlPost($this->url['tranferIn'], $param);

        return $result;
    }

    public function genOtpTranFer($tranId, $type = 'OUT', $otpType = 5)
    {
        if ($otpType == 1) {
            $solveCaptcha = $this->solveCaptcha();
            if ($solveCaptcha['status'] == false) {
                return $solveCaptcha;
            }
            $param = [
                'DT' => $this->DT,
                'OV' => $this->OV,
                'PM' => $this->PM,
                'E' => $this->getE() ?: '',
                'lang' => $this->lang,
                'tranId' => $tranId,
                'type' => $otpType, // 1 là SMS,5 là smart otp
                'captchaToken' => $this->captchaToken,
                'captchaValue' => $this->captchaValue,
                'browserId' => $this->browserId,
                'mid' => 17,
                'cif' => $this->cif,
                'user' => $this->username,
                'mobileId' => $this->mobileId,
                'clientId' => $this->clientId,
                'sessionId' => $this->sessionId,
            ];
        } else {
            $param = [
                'DT' => $this->DT,
                'OV' => $this->OV,

                'PM' => $this->PM,
                'E' => $this->getE() ?: '',
                'lang' => $this->lang,
                'tranId' => $tranId,
                'type' => $otpType, // 1 là SMS,5 là smart otp
                'mid' => 17,
                'browserId' => $this->browserId,
                'cif' => $this->cif,
                'user' => $this->username,
                'mobileId' => $this->mobileId,
                'clientId' => $this->clientId,
                'sessionId' => $this->sessionId,
            ];
        }
        if ($type == 'IN') {
            $result = $this->curlPost($this->url['genOtpIn'], $param);
        } else {
            $result = $this->curlPost($this->url['genOtpOut'], $param);
        }

        return $result;
    }

    public function confirmTranfer($tranId, $challenge, $otp, $type = 'OUT', $otpType = 5)
    {
        if ($otpType == 5) {
            $param = [
                'DT' => $this->DT,
                'OV' => $this->OV,
                'PM' => $this->PM,
                'E' => $this->getE() ?: '',
                'lang' => $this->lang,
                'tranId' => $tranId,
                'otp' => $otp,
                'challenge' => $challenge,
                'mid' => 18,
                'cif' => $this->cif,
                'user' => $this->username,
                'browserId' => $this->browserId,
                'mobileId' => $this->mobileId,
                'clientId' => $this->clientId,
                'sessionId' => $this->sessionId,
            ];
        } else {
            $param = [
                'DT' => $this->DT,
                'OV' => $this->OV,
                'PM' => $this->PM,
                'E' => $this->getE() ?: '',
                'browserId' => $this->browserId,
                'lang' => $this->lang,
                'tranId' => $tranId,
                'otp' => $otp,
                'challenge' => $challenge,
                'mid' => 18,
                'cif' => $this->cif,
                'user' => $this->username,
                'mobileId' => $this->mobileId,
                'clientId' => $this->clientId,
                'sessionId' => $this->sessionId,
            ];
        }

        if ($type == 'IN') {
            $result = $this->curlPost($this->url['confirmTranferIn'], $param);
        } else {
            $result = $this->curlPost($this->url['confirmTranferOut'], $param);
        }

        return $result;
    }

    private function curlPost($url = '', $data = [])
    {
        try {
            $client = new Client(['http_errors' => false]);
            $res = $client->request('POST', $url, [
                'timeout' => $this->_timeout,
                'proxy' => $this->proxy,

                'headers' => $this->headerNull(),
                'body' => json_encode($this->encryptData($data)),
            ]);
            $rawBody = $res->getBody()->getContents();
            $result = json_decode($rawBody);

            if (! is_object($result) || ! isset($result->d) || ! isset($result->k)) {
                return $result;
            }

            return $this->decryptData($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function encryptData($str)
    {
        $str['clientPubKey'] = $this->clientPublicKey;

        $key = Str::random(32);
        $iv = Str::random(16);
        $body = base64_encode($iv.openssl_encrypt(json_encode($str), 'AES-256-CTR', $key, OPENSSL_RAW_DATA, $iv));
        $encryptedKey = '';
        $publicKey = openssl_pkey_get_public($this->defaultPublicKey);

        if ($publicKey === false) {
            return [
                'd' => $body,
                'k' => '',
            ];
        }

        openssl_public_encrypt(base64_encode($key), $encryptedKey, $publicKey, OPENSSL_PKCS1_PADDING);
        $header = base64_encode($encryptedKey);

        return [
            'd' => $body,
            'k' => $header,
        ];
    }

    private function decryptData($cipher)
    {
        $header = $cipher->k;
        $body = base64_decode($cipher->d);
        $decryptedKey = '';
        $privateKey = openssl_pkey_get_private($this->clientPrivateKey);
        if ($privateKey === false) {
            return null;
        }

        openssl_private_decrypt(base64_decode($header), $decryptedKey, $privateKey, OPENSSL_PKCS1_PADDING);
        $key = $decryptedKey;

        $iv = substr($body, 0, 16);
        $cipherText = substr($body, 16);
        $aesKey = base64_decode($key, true);
        if (! is_string($aesKey) || $aesKey === '') {
            return null;
        }
        $text = openssl_decrypt($cipherText, 'AES-256-CTR', $aesKey, OPENSSL_RAW_DATA, $iv);

        return json_decode($text);
    }

    private function headerNull()
    {
        return [
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'vi',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json',
            'Host' => 'digiapp.vietcombank.com.vn',
            'Origin' => 'https://vcbdigibank.vietcombank.com.vn',
            'Referer' => 'https://vcbdigibank.vietcombank.com.vn/',
            'sec-ch-ua-mobile' => '?0',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'X-Channel' => 'Web',
        ];
    }
}
