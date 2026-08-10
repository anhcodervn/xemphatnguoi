<?php

namespace App\Utils;

use InvalidArgumentException;

class RandomHelper
{
    private const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    private function __construct() {}

    /**
     * Tạo chuỗi ngẫu nhiên chỉ gồm chữ hoa và chữ thường theo tập A-Z, a-z.
     */
    public static function RandText(int $number): string
    {
        return self::generate($number, self::LETTERS);
    }

    /**
     * Tạo chuỗi ngẫu nhiên gồm chữ hoa, chữ thường và chữ số theo tập A-Z, a-z, 0-9.
     */
    public static function RandAll(int $number): string
    {
        return self::generate($number, self::ALPHANUMERIC);
    }

    /**
     * Sinh từng ký tự bằng random_int để tránh độ lệch phân phối của phép chia dư.
     */
    private static function generate(int $number, string $characters): string
    {
        if ($number < 0) {
            throw new InvalidArgumentException('Độ dài chuỗi ngẫu nhiên không được nhỏ hơn 0.');
        }

        $result = '';
        $lastIndex = strlen($characters) - 1;

        for ($index = 0; $index < $number; $index++) {
            $result .= $characters[random_int(0, $lastIndex)];
        }

        return $result;
    }
}
