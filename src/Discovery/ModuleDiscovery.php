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
     * @return array<int, string> Absolute route file paths.
     */
    public function routeFiles(): array
    {
        return $this->existingFiles('Infrastructure/Http/routes.php');
    }

    /**
     * @return array<int, string> Absolute service provider file paths.
     */
    public function providerFiles(): array
    {
        $providers = [];

        foreach ($this->modules() as $modulePath) {
            $matches = glob($modulePath . DIRECTORY_SEPARATOR . 'Infrastructure/Providers/*ServiceProvider.php') ?: [];
            sort($matches);
            $providers = array_merge($providers, $matches);
        }

        return $providers;
    }

    /**
     * @return array<int, string>
     */
    private function existingFiles(string $relativePath): array
    {
        $files = [];

        foreach ($this->modules() as $modulePath) {
            $file = $modulePath . DIRECTORY_SEPARATOR . $relativePath;

            if (is_file($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
