<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\ResolvesModules;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

class MakeIntegrationCommand extends Command
{
    use ResolvesModules;
    use WritesFiles;

    protected $signature = 'make:integration {module : The target module} {name : The integration name} {--force : Overwrite existing files}';

    protected $description = 'Create an external integration inside a module infrastructure layer.';

    protected Filesystem $files;

    protected ModulePaths $modulePaths;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $stubs = new StubRenderer($this->laravel, $files);
        $force = (bool) $this->option('force');
        try {
            $this->modulePaths = new ModulePaths($this->laravel);
            $module = $this->modulePaths->moduleName((string) $this->argument('module'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->requireExistingModule($module)) {
            return self::FAILURE;
        }

        $integration = Str::studly((string) $this->argument('name'));
        $namespace = $this->modulePaths->moduleNamespace($module) . '\\Infrastructure\\Integrations\\' . $integration;
        $directory = $this->modulePaths->modulePath($module) . DIRECTORY_SEPARATOR . 'Infrastructure/Integrations/' . $integration;

        $this->ensureDirectoryExists($directory . DIRECTORY_SEPARATOR . 'DTO');
        $this->ensureDirectoryExists($directory . DIRECTORY_SEPARATOR . 'Exceptions');

        $filesToWrite = [
            "{$integration}Client.php" => 'integration-client.stub',
            "{$integration}Adapter.php" => 'integration-adapter.stub',
            "{$integration}Mapper.php" => 'integration-mapper.stub',
        ];

        $created = false;
        foreach ($filesToWrite as $filename => $stub) {
            $created = $this->writeFile(
                $directory . DIRECTORY_SEPARATOR . $filename,
                $stubs->render($stub, [
                    'namespace' => $namespace,
                    'integration' => $integration,
                    'module' => $module,
                ]),
                $force,
            ) || $created;
        }

        return $created ? self::SUCCESS : self::FAILURE;
    }
}
