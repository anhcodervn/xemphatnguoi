<?php

namespace App\Features\TrafficFine\DTOs;

final class TrafficFineLookupResponseDto
{
    public function __construct(
        public readonly TrafficFineLookupResultDataDto $data,
        public readonly bool $cached,
    ) {}

    /**
     * @return array{success: true, cached: bool, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'success' => true,
            'cached' => $this->cached,
            'data' => $this->data->toArray(),
        ];
    }
}
