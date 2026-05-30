<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FeatureCommandSupport
{
    /**
     * @return list<string>
     */
    public function featureDirectories(): array
    {
        return [
            'Controllers',
            'Requests',
            'Actions',
            'Services',
            'DTOs',
            'Resources',
        ];
    }

    public function normalizeFeatureName(string $name): string
    {
        return collect(preg_split('/[\/\\\\]+/', $name) ?: [])
            ->filter()
            ->map(fn (string $segment): string => Str::studly($segment))
            ->implode('\\');
    }

    public function normalizeClassName(string $name): string
    {
        return Str::studly($name);
    }

    public function qualifyClassName(string $name, string $suffix = ''): string
    {
        $className = $this->normalizeClassName($name);

        if ($suffix !== '' && ! Str::endsWith($className, $suffix)) {
            return "{$className}{$suffix}";
        }

        return $className;
    }

    public function routePath(string $featureName): string
    {
        return collect(explode('\\', $featureName))
            ->map(fn (string $segment): string => Str::kebab($segment))
            ->implode('/');
    }

    public function featureBasePath(string $featureName): string
    {
        return app_path('Features/'.str_replace('\\', '/', $featureName));
    }

    public function featureNamespace(string $featureName, string $directory): string
    {
        return "App\\Features\\{$featureName}\\{$directory}";
    }

    public function featureExists(string $featureName): bool
    {
        return File::exists($this->featureBasePath($featureName));
    }

    public function ensureFeatureDirectories(string $featureName): void
    {
        foreach ($this->featureDirectories() as $directory) {
            File::ensureDirectoryExists($this->featureDirectoryPath($featureName, $directory));
        }
    }

    public function featureDirectoryPath(string $featureName, string $directory): string
    {
        return "{$this->featureBasePath($featureName)}/{$directory}";
    }

    public function featureFilePath(string $featureName, string $directory, string $fileName): string
    {
        return $this->featureDirectoryPath($featureName, $directory)."/{$fileName}";
    }

    public function featureRootFilePath(string $featureName, string $fileName): string
    {
        return $this->featureBasePath($featureName)."/{$fileName}";
    }

    public function writeFile(string $path, string $contents): bool
    {
        if (File::exists($path)) {
            return false;
        }

        File::put($path, $contents);

        return true;
    }

    public function featureRoutesImport(string $featureName): string
    {
        $featurePath = str_replace('\\', '/', $featureName);

        return "require base_path('app/Features/{$featurePath}/routes.php');";
    }

    public function featureRoutesImportBlock(string $featureName): string
    {
        $featureRelativePath = $this->featureRelativePath($featureName);
        $featureRoutesImport = $this->featureRoutesImport($featureName);

        return <<<PHP
if (file_exists(base_path('app/Features/{$featureRelativePath}/routes.php'))) {
    {$featureRoutesImport}
}
PHP;
    }

    public function featureRelativePath(string $featureName): string
    {
        return str_replace('\\', '/', $featureName);
    }

    public function featureBaseName(string $featureName): string
    {
        return Str::afterLast($featureName, '\\');
    }

    public function routeFilePath(string $channel): string
    {
        return base_path("routes/{$channel}.php");
    }

    public function normalizeRouteChannel(?string $channel): string
    {
        $normalizedChannel = strtolower((string) $channel);

        return in_array($normalizedChannel, ['web', 'api'], true) ? $normalizedChannel : 'web';
    }

    public function displayPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    public function appendFeatureRoutesImport(string $featureName, string $channel = 'web'): bool
    {
        $routeFilePath = $this->routeFilePath($channel);
        $featureRoutesImport = $this->featureRoutesImport($featureName);
        $routeFileContents = File::get($routeFilePath);

        if (Str::contains($routeFileContents, $featureRoutesImport)) {
            return false;
        }

        File::put(
            $routeFilePath,
            rtrim($routeFileContents).PHP_EOL.PHP_EOL.$this->featureRoutesImportBlock($featureName).PHP_EOL
        );

        return true;
    }
}
