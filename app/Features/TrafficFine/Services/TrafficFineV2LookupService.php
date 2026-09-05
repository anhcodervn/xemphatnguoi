<?php

namespace App\Features\TrafficFine\Services;

use App\Features\TrafficFine\DTOs\TrafficFineLookupResponseDto;
use App\Features\TrafficFine\Services\Source\Xephatnguoi\XephatnguoiV2Source;
use App\Models\User;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class TrafficFineV2LookupService
{
    private readonly TrafficFineLookupService $lookupService;

    public function __construct(
        LicensePlateNormalizer $normalizer,
        XephatnguoiV2Source $source,
        CacheFactory $cache,
    ) {
        $this->lookupService = new TrafficFineLookupService($normalizer, $source, $cache);
    }

    public function lookup(
        string $plate,
        string $vehicleType,
        ?User $user = null,
        ?string $ip = null,
    ): TrafficFineLookupResponseDto {
        return $this->lookupService->lookup(
            plate: $plate,
            vehicleType: $vehicleType,
            user: $user,
            ip: $ip,
        );
    }

    public function findCachedResult(string $plate, string $vehicleType): ?TrafficFineLookupResponseDto
    {
        return $this->lookupService->findCachedResult($plate, $vehicleType);
    }
}
