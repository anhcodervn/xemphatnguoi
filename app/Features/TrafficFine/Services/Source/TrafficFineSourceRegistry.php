<?php

namespace App\Features\TrafficFine\Services\Source;

use App\Features\TrafficFine\Exceptions\TrafficFineConfigurationException;
use Illuminate\Contracts\Container\Container;

final class TrafficFineSourceRegistry
{
    public function __construct(private readonly Container $container) {}

    public function activeName(): string
    {
        $sourceName = trim((string) config('traffic-fines.default_source', 'xephatnguoi'));

        return $sourceName === 'third_party' ? 'xephatnguoi' : $sourceName;
    }

    /** @return array<string, mixed> */
    public function activeConfig(): array
    {
        return (array) config("traffic-fines.sources.{$this->activeName()}", []);
    }

    public function resolve(): TrafficFineSourceInterface
    {
        $driver = $this->activeConfig()['driver'] ?? null;

        if (! is_string($driver) || ! is_a($driver, TrafficFineSourceInterface::class, true)) {
            throw new TrafficFineConfigurationException('Nguồn tra cứu không được hỗ trợ.');
        }

        $source = $this->container->make($driver);

        if (! $source instanceof TrafficFineSourceInterface) {
            throw new TrafficFineConfigurationException('Nguồn tra cứu không được hỗ trợ.');
        }

        return $source;
    }
}
