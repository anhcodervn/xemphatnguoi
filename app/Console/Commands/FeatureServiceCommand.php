<?php

namespace App\Console\Commands;

use App\Support\FeatureCommandSupport;
use Illuminate\Console\Command;

class FeatureServiceCommand extends Command
{
    protected $signature = 'feature:service
                            {feature : The feature name}
                            {name : The service name}';

    protected $description = 'Create a service inside a feature module';

    public function handle(FeatureCommandSupport $features): int
    {
        $featureName = $features->normalizeFeatureName((string) $this->argument('feature'));
        $serviceName = $features->qualifyClassName((string) $this->argument('name'), 'Service');

        if (! $features->featureExists($featureName)) {
            $this->error("Feature {$featureName} does not exist. Run feature:create first.");

            return self::FAILURE;
        }

        $path = $features->featureFilePath($featureName, 'Services', "{$serviceName}.php");

        if (! $features->writeFile($path, $this->stub($featureName, $serviceName))) {
            $this->error("Service {$serviceName} already exists in feature {$featureName}.");

            return self::FAILURE;
        }

        $this->info("Service {$serviceName} created successfully in feature {$featureName}.");
        $this->line('  - File: '.$features->displayPath($path));

        return self::SUCCESS;
    }

    protected function stub(string $featureName, string $serviceName): string
    {
        return <<<PHP
<?php

namespace App\Features\\{$featureName}\Services;

class {$serviceName}
{
}
PHP;
    }
}
