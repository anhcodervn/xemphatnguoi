<?php

namespace App\Console\Commands;

use App\Support\FeatureCommandSupport;
use Illuminate\Console\Command;

class FeatureDtoCommand extends Command
{
    protected $signature = 'feature:dto
                            {feature : The feature name}
                            {name : The DTO name}';

    protected $description = 'Create a DTO inside a feature module';

    public function handle(FeatureCommandSupport $features): int
    {
        $featureName = $features->normalizeFeatureName((string) $this->argument('feature'));
        $dtoName = $features->qualifyClassName((string) $this->argument('name'), 'Dto');

        if (! $features->featureExists($featureName)) {
            $this->error("Feature {$featureName} does not exist. Run feature:create first.");

            return self::FAILURE;
        }

        $path = $features->featureFilePath($featureName, 'DTOs', "{$dtoName}.php");

        if (! $features->writeFile($path, $this->stub($featureName, $dtoName))) {
            $this->error("DTO {$dtoName} already exists in feature {$featureName}.");

            return self::FAILURE;
        }

        $this->info("DTO {$dtoName} created successfully in feature {$featureName}.");
        $this->line('  - File: '.$features->displayPath($path));

        return self::SUCCESS;
    }

    protected function stub(string $featureName, string $dtoName): string
    {
        return <<<PHP
<?php

namespace App\Features\\{$featureName}\DTOs;

final class {$dtoName}
{
}
PHP;
    }
}
