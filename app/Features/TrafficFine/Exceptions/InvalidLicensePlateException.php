<?php

namespace App\Features\TrafficFine\Exceptions;

use InvalidArgumentException;

class InvalidLicensePlateException extends InvalidArgumentException
{
    public static function forInput(string $plate): self
    {
        return new self("Biển số {$plate} không đúng định dạng hỗ trợ.");
    }
}
