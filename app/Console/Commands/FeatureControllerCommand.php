<?php

namespace App\Console\Commands;

use App\Support\FeatureCommandSupport;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureControllerCommand extends Command
{
    protected $signature = 'feature:controller
                            {feature : The feature name}
                            {name? : The controller name}
                            {--resource : Generate a resource controller}
                            {--api : Generate an API resource controller}';

    protected $description = 'Create a controller inside a feature module';

    public function handle(FeatureCommandSupport $features): int
    {
        $featureName = $features->normalizeFeatureName((string) $this->argument('feature'));
        $controllerInput = (string) ($this->argument('name') ?: $features->featureBaseName($featureName));
        $controllerName = $features->qualifyClassName($controllerInput, 'Controller');

        if (! $features->featureExists($featureName)) {
            $this->error("Feature {$featureName} does not exist. Run feature:create first.");

            return self::FAILURE;
        }

        $path = $features->featureFilePath($featureName, 'Controllers', "{$controllerName}.php");

        if (! $features->writeFile($path, $this->stub($featureName, $controllerName))) {
            $this->error("Controller {$controllerName} already exists in feature {$featureName}.");

            return self::FAILURE;
        }

        $this->info("Controller {$controllerName} created successfully in feature {$featureName}.");
        $this->line('  - File: '.$features->displayPath($path));

        return self::SUCCESS;
    }

    protected function stub(string $featureName, string $controllerName): string
    {
        if ((bool) $this->option('resource')) {
            return (bool) $this->option('api')
                ? $this->apiResourceControllerStub($featureName, $controllerName)
                : $this->resourceControllerStub($featureName, $controllerName);
        }

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
            'message' => '{$controllerName} index',
        ]);
    }
}
PHP;
    }

    protected function apiResourceControllerStub(string $featureName, string $controllerName): string
    {
        return <<<PHP
<?php

namespace App\Features\\{$featureName}\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class {$controllerName} extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request \$request): JsonResponse
    {
        return response()->json([], 201);
    }

    public function show(string \$id): JsonResponse
    {
        return response()->json([
            'id' => \$id,
        ]);
    }

    public function update(Request \$request, string \$id): JsonResponse
    {
        return response()->json([
            'id' => \$id,
        ]);
    }

    public function destroy(string \$id): JsonResponse
    {
        return response()->json([], 204);
    }
}
PHP;
    }

    protected function resourceControllerStub(string $featureName, string $controllerName): string
    {
        return <<<PHP
<?php

namespace App\Features\\{$featureName}\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class {$controllerName} extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    public function create(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request \$request): JsonResponse
    {
        return response()->json([], 201);
    }

    public function show(string \$id): JsonResponse
    {
        return response()->json([
            'id' => \$id,
        ]);
    }

    public function edit(string \$id): JsonResponse
    {
        return response()->json([
            'id' => \$id,
        ]);
    }

    public function update(Request \$request, string \$id): JsonResponse
    {
        return response()->json([
            'id' => \$id,
        ]);
    }

    public function destroy(string \$id): JsonResponse
    {
        return response()->json([], 204);
    }
}
PHP;
    }
}
