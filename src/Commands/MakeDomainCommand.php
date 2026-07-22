<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\ModuleStructure;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

class MakeDomainCommand extends Command
{
    use WritesFiles;

    protected $signature = 'make:domain {name : The module/domain name} {--force : Overwrite generated files}';

    protected $description = 'Create a DDD module structure.';

    protected Filesystem $files;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $modulePaths = new ModulePaths($this->laravel);
        $structure = new ModuleStructure();
        $stubs = new StubRenderer($this->laravel, $files);
        try {
            $module = $modulePaths->moduleName((string) $this->argument('name'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $modulePath = $modulePaths->modulePath($module);

        $this->ensureDirectoryExists($modulePath);

        foreach ($structure->directories() as $directory) {
            $this->ensureDirectoryExists($modulePath . DIRECTORY_SEPARATOR . $directory);
        }

        $routesPath = $modulePath . DIRECTORY_SEPARATOR . 'Infrastructure/Http/routes.php';

        if (str_contains(implode('|', $structure->directories()), 'Infrastructure/Http')) {
            $this->writeFile(
                $routesPath,
                $stubs->render('module-routes.stub', ['module' => $module]),
                (bool) $this->option('force'),
            );
        }

        $this->components->info("Module [{$module}] is ready.");

        return self::SUCCESS;
    }
}
