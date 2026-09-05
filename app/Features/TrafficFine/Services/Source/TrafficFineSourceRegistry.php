<?php

namespace App\Features\TrafficFine\Services\Source;

use App\Features\TrafficFine\Exceptions\TrafficFineConfigurationException;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use Illuminate\Contracts\Container\Container;

final class TrafficFineSourceRegistry
{
    public function __construct(
        private readonly Container $container,
        private readonly TrafficFineProviderSettingsService $providerSettings,
    ) {}

    public function activeName(): string
    {
        return $this->providerSettings->activeName();
    }

    /** @return array<string, mixed> */
    public function activeConfig(): array
    {
        return $this->providerSettings->configuration($this->activeName());
    }

    public function resolve(): TrafficFineSourceInterface
    {
        $activeName = $this->activeName();

        if ($activeName === '') {
            throw new TrafficFineConfigurationException('Chưa bật nguồn tra cứu nào.');
        }

        $driver = $this->providerSettings->configuration($activeName)['driver'] ?? null;

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
