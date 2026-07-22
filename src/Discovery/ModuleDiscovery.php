<?php

namespace SamuelNunes\LaravelDddToolkit\Discovery;

use Illuminate\Contracts\Foundation\Application;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;

class ModuleDiscovery
{
    /**
     * @var array<int, string>|null
     */
    private ?array $modules = null;

    /**
     * @var array<string, array<int, string>>|null
     */
    private ?array $manifest = null;

    public function __construct(private readonly Application $app)
    {
    }

    /**
     * Returns absolute directory paths for all discovered modules.
     *
     * @return array<int, string> Absolute module directory paths.
     */
    public function modules(): array
    {
        $manifest = $this->manifest();

        if ($manifest !== null) {
            return $manifest['modules'] ?? [];
        }

        return $this->discoverModules();
    }

    /**
     * @return array<int, string> Absolute route file paths.
     */
    public function routeFiles(): array
    {
        $manifest = $this->manifest();

        if ($manifest !== null) {
            return $manifest['routes'] ?? [];
        }

        return $this->existingFiles('Infrastructure/Http/routes.php', $this->discoverModules());
    }

    /**
     * @return array<int, string> Absolute service provider file paths.
     */
    public function providerFiles(): array
    {
        $manifest = $this->manifest();

        if ($manifest !== null) {
            return $manifest['providers'] ?? [];
        }

        return $this->discoverProviderFiles($this->discoverModules());
    }

    /**
     * @return array{modules: array<int, string>, providers: array<int, string>, routes: array<int, string>}
     */
    public function buildManifest(): array
    {
        $modules = $this->discoverModules();

        return [
            'modules' => $modules,
            'providers' => $this->discoverProviderFiles($modules),
            'routes' => $this->existingFiles('Infrastructure/Http/routes.php', $modules),
        ];
    }

    public function manifestPath(): string
    {
        return $this->app->bootstrapPath('cache/ddd-modules.php');
    }

    /**
     * @return array<int, string>
     */
    private function discoverModules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modulesPath = (new ModulePaths($this->app))->modulesPath();

        if (! is_dir($modulesPath)) {
            return $this->modules = [];
        }

        $modules = glob($modulesPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

        sort($modules);

        return $this->modules = $modules;
    }

    /**
     * @param array<int, string> $modules
     * @return array<int, string>
     */
    private function discoverProviderFiles(array $modules): array
    {
        $providers = [];

        foreach ($modules as $modulePath) {
            $matches = glob($modulePath . DIRECTORY_SEPARATOR . 'Infrastructure/Providers/*ServiceProvider.php') ?: [];
            sort($matches);
            $providers = array_merge($providers, $matches);
        }

        return $providers;
    }

    /**
     * @param array<int, string> $modules
     * @return array<int, string>
     */
    private function existingFiles(string $relativePath, array $modules): array
    {
        $files = [];

        foreach ($modules as $modulePath) {
            $file = $modulePath . DIRECTORY_SEPARATOR . $relativePath;

            if (is_file($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @return array<string, array<int, string>>|null
     */
    private function manifest(): ?array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $path = $this->manifestPath();

        if (! is_file($path)) {
            return null;
        }

        $manifest = require $path;

        if (! is_array($manifest)) {
            return null;
        }

        return $this->manifest = [
            'modules' => array_values(array_map('strval', $manifest['modules'] ?? [])),
            'providers' => array_values(array_map('strval', $manifest['providers'] ?? [])),
            'routes' => array_values(array_map('strval', $manifest['routes'] ?? [])),
        ];
    }
}
