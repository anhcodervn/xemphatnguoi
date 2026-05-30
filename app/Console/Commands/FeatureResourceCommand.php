<?php

namespace App\Console\Commands;

use App\Support\FeatureCommandSupport;
use Illuminate\Console\Command;

class FeatureResourceCommand extends Command
{
    protected $signature = 'feature:resource
                            {feature : The feature name}
                            {name : The resource name}';

    protected $description = 'Create a JSON resource inside a feature module';

    public function handle(FeatureCommandSupport $features): int
    {
        $featureName = $features->normalizeFeatureName((string) $this->argument('feature'));
        $resourceName = $features->qualifyClassName((string) $this->argument('name'), 'Resource');

        if (! $features->featureExists($featureName)) {
            $this->error("Feature {$featureName} does not exist. Run feature:create first.");

            return self::FAILURE;
        }

        $path = $features->featureFilePath($featureName, 'Resources', "{$resourceName}.php");

        if (! $features->writeFile($path, $this->stub($featureName, $resourceName))) {
            $this->error("Resource {$resourceName} already exists in feature {$featureName}.");

            return self::FAILURE;
        }

        $this->info("Resource {$resourceName} created successfully in feature {$featureName}.");
        $this->line('  - File: '.$features->displayPath($path));

        return self::SUCCESS;
    }

    protected function stub(string $featureName, string $resourceName): string
    {
        return <<<PHP
<?php

namespace App\Features\\{$featureName}\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$resourceName} extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return parent::toArray(\$request);
    }
}
PHP;
    }
}
