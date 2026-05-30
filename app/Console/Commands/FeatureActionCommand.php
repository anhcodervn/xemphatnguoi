<?php

namespace App\Console\Commands;

use App\Support\FeatureCommandSupport;
use Illuminate\Console\Command;

class FeatureActionCommand extends Command
{
    protected $signature = 'feature:action
                            {feature : The feature name}
                            {name : The action name}';

    protected $description = 'Create an action inside a feature module';

    public function handle(FeatureCommandSupport $features): int
    {
        $featureName = $features->normalizeFeatureName((string) $this->argument('feature'));
        $actionName = $features->qualifyClassName((string) $this->argument('name'), 'Action');

        if (! $features->featureExists($featureName)) {
            $this->error("Feature {$featureName} does not exist. Run feature:create first.");

            return self::FAILURE;
        }

        $path = $features->featureFilePath($featureName, 'Actions', "{$actionName}.php");

        if (! $features->writeFile($path, $this->stub($featureName, $actionName))) {
            $this->error("Action {$actionName} already exists in feature {$featureName}.");

            return self::FAILURE;
        }

        $this->info("Action {$actionName} created successfully in feature {$featureName}.");
        $this->line('  - File: '.$features->displayPath($path));

        return self::SUCCESS;
    }

    protected function stub(string $featureName, string $actionName): string
    {
        return <<<PHP
<?php

namespace App\Features\\{$featureName}\Actions;

class {$actionName}
{
    public function handle(): void
    {
    }
}
PHP;
    }
}
