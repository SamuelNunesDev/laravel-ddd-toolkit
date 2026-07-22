<?php

namespace SamuelNunes\LaravelDddToolkit\Commands\Concerns;

use Illuminate\Console\Command;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;

/**
 * @mixin Command
 */
trait ResolvesModules
{
    protected function resolveModuleName(): ?string
    {
        $module = $this->option('module');

        if (is_string($module) && $module !== '') {
            return $this->modulePaths->moduleName($module);
        }

        $inferredModule = $this->modulePaths->inferModuleFromCurrentWorkingDirectory();

        return $inferredModule ? $this->modulePaths->moduleName($inferredModule) : null;
    }

    protected function requireExistingModule(string $module): bool
    {
        if ($this->files->isDirectory($this->modulePaths->modulePath($module))) {
            return true;
        }

        $this->components->error("Module [{$module}] does not exist. Run [php artisan make:domain {$module}] first.");

        return false;
    }

    protected function modulePaths(): ModulePaths
    {
        return $this->modulePaths;
    }
}
