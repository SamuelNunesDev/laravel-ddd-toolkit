<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\ModuleStructure;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

class MakeModuleCommand extends Command
{
    use WritesFiles;

    protected $signature = 'make:module {name : The module name} {--preset= : Structure preset: hexagonal, minimal, tactical, or full} {--force : Overwrite generated files}';

    protected $description = 'Create a DDD module structure.';

    protected Filesystem $files;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $modulePaths = new ModulePaths($this->laravel);
        $structure = new ModuleStructure();
        $stubs = new StubRenderer($this->laravel, $files);
        try {
            $preset = $this->preset();
            $module = $modulePaths->moduleName((string) $this->argument('name'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $modulePath = $modulePaths->modulePath($module);

        $this->ensureDirectoryExists($modulePath);

        foreach ($structure->directories($preset) as $directory) {
            $this->ensureDirectoryExists($modulePath . DIRECTORY_SEPARATOR . $directory);
        }

        $routesPath = $modulePath . DIRECTORY_SEPARATOR . 'Infrastructure/Http/routes.php';

        if (str_contains(implode('|', $structure->directories($preset)), 'Infrastructure/Http')) {
            $this->writeFile(
                $routesPath,
                $stubs->render('module-routes.stub', ['module' => $module]),
                (bool) $this->option('force'),
            );
        }

        $this->components->info("Module [{$module}] is ready.");

        return self::SUCCESS;
    }

    private function preset(): string
    {
        $preset = (string) ($this->option('preset') ?: config('ddd.default_preset', 'hexagonal'));
        $presets = array_unique(array_merge(
            array_keys((require __DIR__ . '/../../config/ddd.php')['presets'] ?? []),
            array_keys(is_array(config('ddd.presets')) ? config('ddd.presets') : []),
        ));

        if (! in_array($preset, $presets, true)) {
            throw new InvalidArgumentException("Invalid module preset [{$preset}].");
        }

        return $preset;
    }
}
