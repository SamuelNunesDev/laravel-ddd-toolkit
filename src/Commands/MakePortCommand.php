<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

class MakePortCommand extends Command
{
    use WritesFiles;

    protected $signature = 'make:port {module : The target module} {name : The port interface name} {--type=out : Port type: in or out} {--force : Overwrite existing files}';

    protected $description = 'Create an application port interface inside a module.';

    protected Filesystem $files;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $modulePaths = new ModulePaths($this->laravel);
        $stubs = new StubRenderer($this->laravel, $files);

        try {
            $module = $modulePaths->moduleName((string) $this->argument('module'));
            $type = $this->portType();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $files->isDirectory($modulePaths->modulePath($module))) {
            $this->components->error("Module [{$module}] does not exist. Run [php artisan make:module {$module}] first.");

            return self::FAILURE;
        }

        $class = Str::studly((string) $this->argument('name'));
        $segment = $type === 'in' ? 'In' : 'Out';
        $namespace = $modulePaths->moduleNamespace($module) . "\\Application\\Ports\\{$segment}";
        $path = $modulePaths->modulePath($module) . DIRECTORY_SEPARATOR . "Application/Ports/{$segment}/{$class}.php";

        return $this->writeFile(
            $path,
            $stubs->render('port.stub', [
                'namespace' => $namespace,
                'class' => $class,
                'module' => $module,
            ]),
            (bool) $this->option('force'),
        ) ? self::SUCCESS : self::FAILURE;
    }

    private function portType(): string
    {
        $type = strtolower((string) $this->option('type'));

        if (! in_array($type, ['in', 'out'], true)) {
            throw new InvalidArgumentException("Invalid port type [{$type}]. Use [in] or [out].");
        }

        return $type;
    }
}
