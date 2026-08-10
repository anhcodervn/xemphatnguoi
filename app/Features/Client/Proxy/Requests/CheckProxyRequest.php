<?php

namespace App\Features\Client\Proxy\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CheckProxyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proxies' => ['required', 'array', 'min:1', 'max:20'],
            'proxies.*' => ['required', 'string', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'proxies.required' => 'Vui lòng nhập ít nhất một proxy.',
            'proxies.array' => 'Danh sách proxy không đúng định dạng.',
            'proxies.min' => 'Vui lòng nhập ít nhất một proxy.',
            'proxies.max' => 'Mỗi lần chỉ được kiểm tra tối đa 20 proxy.',
            'proxies.*.required' => 'Dòng proxy không được để trống.',
            'proxies.*.string' => 'Dòng proxy không đúng định dạng.',
            'proxies.*.max' => 'Dòng proxy không được dài quá 512 ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'proxies' => 'danh sách proxy',
            'proxies.*' => 'proxy',
        ];
    }

    /**
     * Chuẩn hóa textarea thành danh sách từng dòng trước khi validate.
     */
    protected function prepareForValidation(): void
    {
        $proxies = $this->input('proxies');

        if (! is_string($proxies)) {
            return;
        }

        $lines = preg_split('/\R/u', $proxies) ?: [];

        $this->merge([
            'proxies' => array_values(array_filter(
                array_map(static fn (string $line): string => trim($line), $lines),
                static fn (string $line): bool => $line !== '',
            )),
        ]);
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ((array) $this->input('proxies', []) as $index => $proxy) {
                    if (! is_string($proxy) || ! $this->isValidProxy($proxy)) {
                        $line = $index + 1;
                        $validator->errors()->add(
                            "proxies.{$index}",
                            "Proxy ở dòng {$line} phải có định dạng IP:PORT:USER:PASS và sử dụng IPv4 public.",
                        );
                    }
                }
            },
        ];
    }

    private function isValidProxy(string $proxy): bool
    {
        $parts = explode(':', trim($proxy), 4);

        if (count($parts) !== 4) {
            return false;
        }

        [$ip, $port, $username, $password] = $parts;
        $validIp = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );

        return $validIp !== false
            && ctype_digit($port)
            && (int) $port >= 1
            && (int) $port <= 65535
            && trim($username) !== ''
            && mb_strlen($username) <= 128
            && trim($password) !== ''
            && mb_strlen($password) <= 256;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
