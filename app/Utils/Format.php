<?php

namespace App\Utils;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;

class Format
{
    public static function cash(
        mixed $number,
        string $suffix = '',
        int $decimals = 0,
        string $default = '0'
    ): string {
        if (!is_numeric($number)) {
            return $default;
        }

        return self::number($number, $decimals) . $suffix;
    }

    public static function number(
        mixed $number,
        int $decimals = 0,
        string $decimalSeparator = '.',
        string $thousandSeparator = ',',
        string $default = '0'
    ): string {
        if (!is_numeric($number)) {
            return $default;
        }

        return number_format((float) $number, $decimals, $decimalSeparator, $thousandSeparator);
    }

    public static function compactNumber(
        mixed $number,
        int $decimals = 1,
        string $default = '0'
    ): string {
        if (!is_numeric($number)) {
            return $default;
        }

        $number = (float) $number;
        $absNumber = abs($number);

        if ($absNumber >= 1_000_000_000) {
            return rtrim(rtrim(number_format($number / 1_000_000_000, $decimals, '.', ''), '0'), '.') . 'B';
        }

        if ($absNumber >= 1_000_000) {
            return rtrim(rtrim(number_format($number / 1_000_000, $decimals, '.', ''), '0'), '.') . 'M';
        }

        if ($absNumber >= 1_000) {
            return rtrim(rtrim(number_format($number / 1_000, $decimals, '.', ''), '0'), '.') . 'K';
        }

        return self::number($number, 0);
    }

    public static function percent(
        mixed $number,
        int $decimals = 0,
        string $default = '0%'
    ): string {
        if (!is_numeric($number)) {
            return $default;
        }

        return self::number($number, $decimals) . '%';
    }

    public static function priceRange(
        mixed $minPrice,
        mixed $maxPrice,
        string $separator = ' - ',
        string $default = 'Liên hệ'
    ): string {
        if (!is_numeric($minPrice) && !is_numeric($maxPrice)) {
            return $default;
        }

        if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $minPrice === (float) $maxPrice) {
            return self::cash($minPrice);
        }

        if (!is_numeric($minPrice)) {
            return self::cash($maxPrice);
        }

        if (!is_numeric($maxPrice)) {
            return self::cash($minPrice);
        }

        return self::cash($minPrice) . $separator . self::cash($maxPrice);
    }

    public static function boolean(
        mixed $value,
        string $trueText = 'Có',
        string $falseText = 'Không'
    ): string {
        return filter_var($value, FILTER_VALIDATE_BOOL) ? $trueText : $falseText;
    }

    public static function status(
        mixed $value,
        string $activeText = 'Đang hoạt động',
        string $inactiveText = 'Tạm dừng'
    ): string {
        return filter_var($value, FILTER_VALIDATE_BOOL) ? $activeText : $inactiveText;
    }

    public static function phone(?string $phone, string $default = ''): string
    {
        if (!$phone) {
            return $default;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 10) {
            return preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', $digits) ?? $phone;
        }

        if (strlen($digits) === 11) {
            return preg_replace('/(\d{4})(\d{3})(\d{4})/', '$1 $2 $3', $digits) ?? $phone;
        }

        return $phone;
    }

    public static function maskPhone(?string $phone, string $default = ''): string
    {
        if (!$phone) {
            return $default;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 7) {
            return $phone;
        }

        return substr($digits, 0, 3) . str_repeat('*', max(strlen($digits) - 6, 1)) . substr($digits, -3);
    }

    public static function maskEmail(?string $email, string $default = ''): string
    {
        if (!$email || !str_contains($email, '@')) {
            return $default ?: (string) $email;
        }

        [$name, $domain] = explode('@', $email, 2);

        if (strlen($name) <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 1));
        }

        return $maskedName . '@' . $domain;
    }

    public static function date(
        DateTimeInterface|string|int|null $value,
        string $format = 'd/m/Y',
        ?string $timezone = null,
        string $default = ''
    ): string {
        $date = self::parseDate($value, $timezone);

        return $date?->format($format) ?? $default;
    }

    public static function datetime(
        DateTimeInterface|string|int|null $value,
        string $format = 'd/m/Y H:i',
        ?string $timezone = null,
        string $default = ''
    ): string {
        return self::date($value, $format, $timezone, $default);
    }

    public static function time(
        DateTimeInterface|string|int|null $value,
        string $format = 'H:i',
        ?string $timezone = null,
        string $default = ''
    ): string {
        return self::date($value, $format, $timezone, $default);
    }

    public static function relativeTime(
        DateTimeInterface|string|int|null $value,
        ?string $timezone = null,
        string $default = ''
    ): string {
        $date = self::parseDate($value, $timezone);

        return $date?->diffForHumans() ?? $default;
    }

    public static function bytes(mixed $bytes, int $precision = 2, string $default = '0 B'): string
    {
        if (!is_numeric($bytes)) {
            return $default;
        }

        $bytes = max((float) $bytes, 0);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), $precision, '.', ',') . ' ' . $units[$power];
    }

    public static function truncate(
        ?string $text,
        int $limit = 120,
        string $end = '...'
    ): string {
        return Str::limit(trim((string) $text), $limit, $end);
    }

    public static function plainText(?string $text, int $limit = 0): string
    {
        $text = trim(strip_tags((string) $text));

        if ($limit > 0) {
            return Str::limit($text, $limit);
        }

        return $text;
    }

    public static function initials(?string $text, int $maxLetters = 2, string $default = '?'): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return $default;
        }

        $parts = preg_split('/\s+/u', $text) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take($maxLetters)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : $default;
    }

    private static function parseDate(
        DateTimeInterface|string|int|null $value,
        ?string $timezone = null
    ): ?Carbon {
        if ($value === null || $value === '') {
            return null;
        }

        $timezone ??= config('app.timezone', 'Asia/Bangkok');

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->setTimezone($timezone);
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value, $timezone);
        }

        try {
            return Carbon::parse($value, $timezone);
        } catch (\Throwable) {
            return null;
        }
    }
}
