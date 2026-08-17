<?php

namespace App\Features\TrafficFine\Exceptions;

use InvalidArgumentException;

class UnsupportedVehicleTypeException extends InvalidArgumentException
{
    public static function forType(string $vehicleType): self
    {
        return new self("Loại phương tiện {$vehicleType} hiện không được hỗ trợ.");
    }
}
