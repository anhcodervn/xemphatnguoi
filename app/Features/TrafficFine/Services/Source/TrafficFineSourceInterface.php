<?php

namespace App\Features\TrafficFine\Services\Source;

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\VehicleType;

interface TrafficFineSourceInterface
{
    public function name(): string;

    public function lookup(string $normalizedPlate, VehicleType $vehicleType): TrafficFineLookupResultDataDto;
}
