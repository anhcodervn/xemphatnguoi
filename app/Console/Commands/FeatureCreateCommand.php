<?php

namespace App\Console\Commands;

use App\Support\FeatureCommandSupport;
use Illuminate\Console\Command;
class FeatureCreateCommand extends Command
{
    protected $signature = 'feature:create
                            {name : The feature name}
                            {--route=web : The route channel to register the feature in (web or api)}';

    protected $description = 'Create a new feature module scaffold';

    public function handle(FeatureCommandSupport $features): int
    {
        $featureName = $features->normalizeFeatureName((string) $this->argument('name'));
        $featureBaseName = $features->featureBaseName($featureName);
        $routeChannel = $features->normalizeRouteChannel((string) $this->option('route'));
        $routeFilePath = $features->routeFilePath($routeChannel);

        if ($features->featureExists($featureName)) {
            $this->error("Feature {$featureName} already exists.");

            return self::FAILURE;
        }

        $features->ensureFeatureDirectories($featureName);

        $controllerName = "{$featureBaseName}Controller";
        $controllerPath = $features->featureFilePath($featureName, 'Controllers', "{$controllerName}.php");
        $routesPath = $features->featureRootFilePath($featureName, 'routes.php');
        $readmePath = $features->featureRootFilePath($featureName, 'README.md');

        $features->writeFile($controllerPath, $this->controllerStub($featureName, $featureBaseName, $controllerName));
        $features->writeFile($routesPath, $this->routesStub($featureName, $controllerName, $features->routePath($featureName)));
        $features->writeFile($readmePath, "# {$featureName} Feature\n");
        $features->appendFeatureRoutesImport($featureName, $routeChannel);

        $this->info("Feature {$featureName} created successfully.");
        $this->line("  - Controller: {$features->displayPath($controllerPath)}");
        $this->line("  - Routes: {$features->displayPath($routesPath)}");
        $this->line("  - README: {$features->displayPath($readmePath)}");
        $this->line("  - Route channel: {$routeChannel}");
        $this->line('  - Route import: '.$features->displayPath($routeFilePath));
        $this->line("  - Feature directory: {$features->displayPath($features->featureBasePath($featureName))}");

        return self::SUCCESS;
    }

    protected function controllerStub(string $featureName, string $featureBaseName, string $controllerName): string
    {
        return <<<PHP
<?php

namespace App\Features\\{$featureName}\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class {$controllerName} extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'feature' => '{$featureName}',
            'message' => '{$featureBaseName} feature index',
        ]);
    }
}
PHP;
    }

    protected function routesStub(string $featureName, string $controllerName, string $routePath): string
    {
        return <<<PHP
<?php

use App\Features\\{$featureName}\Controllers\\{$controllerName};
use Illuminate\Support\Facades\Route;

Route::prefix('{$routePath}')
    ->name('{$routePath}.')
    ->group(function (): void {
        Route::get('/', [{$controllerName}::class, 'index'])->name('index');
    });

PHP;
    }
}
