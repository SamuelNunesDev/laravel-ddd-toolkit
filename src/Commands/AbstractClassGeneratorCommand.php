<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

abstract class AbstractClassGeneratorCommand extends Command
{
    use WritesFiles;

    protected Filesystem $files;

    protected ModulePaths $modulePaths;

    protected StubRenderer $stubs;

    abstract protected function stubName(): string;

    abstract protected function relativeNamespace(): string;

    abstract protected function relativeDirectory(): string;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $this->modulePaths = new ModulePaths($this->laravel);
        $this->stubs = new StubRenderer($this->laravel, $files);

        try {
            $module = $this->modulePaths->moduleName((string) $this->argument('module'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->requireExistingModule($module)) {
            return self::FAILURE;
        }

        $class = $this->className();
        $namespace = $this->modulePaths->moduleNamespace($module) . '\\' . trim($this->relativeNamespace(), '\\');
        $path = $this->modulePaths->modulePath($module)
            . DIRECTORY_SEPARATOR
            . trim($this->relativeDirectory(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $class
            . '.php';

        $contents = $this->stubs->render($this->stubName(), [
            'namespace' => $namespace,
            'class' => $class,
            'module' => $module,
        ]);

        return $this->writeFile($path, $contents, (bool) $this->option('force'))
            ? self::SUCCESS
            : self::FAILURE;
    }

    protected function className(): string
    {
        return Str::studly((string) $this->argument('name'));
    }

    protected function requireExistingModule(string $module): bool
    {
        if ($this->files->isDirectory($this->modulePaths->modulePath($module))) {
            return true;
        }

        $this->components->error("Module [{$module}] does not exist. Run [php artisan make:module {$module}] first.");

        return false;
    }
}
