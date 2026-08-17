<?php

namespace App\Features\TrafficFine\Enums;

enum VehicleType: string
{
    case Car = 'car';
    case Motorbike = 'motorbike';
    case ElectricMotorbike = 'electric_motorbike';

    public function label(): string
    {
        return (string) config("traffic-fines.vehicle_types.{$this->value}.label", $this->value);
    }

    public function isEnabled(): bool
    {
        return (bool) config("traffic-fines.vehicle_types.{$this->value}.enabled", false);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $vehicleType): string => $vehicleType->value,
            self::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function enabledValues(): array
    {
        return array_values(array_map(
            static fn (self $vehicleType): string => $vehicleType->value,
            array_filter(self::cases(), static fn (self $vehicleType): bool => $vehicleType->isEnabled()),
        ));
    }
}
