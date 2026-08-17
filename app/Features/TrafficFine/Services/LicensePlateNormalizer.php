<?php

namespace App\Features\TrafficFine\Services;

use App\Features\TrafficFine\Exceptions\InvalidLicensePlateException;
use Illuminate\Support\Str;

class LicensePlateNormalizer
{
    public function normalize(string $plate): string
    {
        $normalizedPlate = Str::of($plate)
            ->trim()
            ->upper()
            ->replaceMatches('/[\s.\-]+/u', '')
            ->toString();

        $pattern = (string) config('traffic-fines.plate_pattern');

        if ($normalizedPlate === '' || preg_match($pattern, $normalizedPlate) !== 1) {
            throw InvalidLicensePlateException::forInput($plate);
        }

        return $normalizedPlate;
    }

    public function display(string $plate): string
    {
        $normalizedPlate = $this->normalize($plate);

        if (preg_match('/^(\d{2}[A-ZĐ]{1,2})(\d{4,6})$/u', $normalizedPlate, $matches) !== 1) {
            return $normalizedPlate;
        }

        $prefix = $matches[1];
        $serial = $matches[2];
        $serialPrefix = Str::substr($serial, 0, -2);
        $serialSuffix = Str::substr($serial, -2);

        return sprintf('%s-%s.%s', $prefix, $serialPrefix, $serialSuffix);
    }
}
