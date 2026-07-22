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

class MakeAclCommand extends Command
{
    use ResolvesModules;
    use WritesFiles;

    protected $signature = 'make:acl {name : The integration name} {--module= : The target module} {--force : Overwrite existing files}';

    protected $description = 'Create an anti-corruption layer for an external integration.';

    protected Filesystem $files;

    protected ModulePaths $modulePaths;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $this->modulePaths = new ModulePaths($this->laravel);
        $stubs = new StubRenderer($this->laravel, $files);
        $force = (bool) $this->option('force');
        try {
            $module = $this->resolveModuleName();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($module === null) {
            $this->components->error('Unable to infer the module. Pass the module explicitly with [--module=ModuleName].');

            return self::FAILURE;
        }

        if (! $this->requireExistingModule($module)) {
            return self::FAILURE;
        }

        $integration = Str::studly((string) $this->argument('name'));
        $namespace = $this->modulePaths->moduleNamespace($module) . '\\Infrastructure\\Integrations\\' . $integration;
        $directory = $this->modulePaths->modulePath($module) . DIRECTORY_SEPARATOR . 'Infrastructure/Integrations/' . $integration;

        $filesToWrite = [
            'Client.php' => 'acl-client.stub',
            'Adapter.php' => 'acl-adapter.stub',
            'Mapper.php' => 'acl-mapper.stub',
            'DTO.php' => 'acl-dto.stub',
        ];

        $created = false;
        foreach ($filesToWrite as $filename => $stub) {
            $class = pathinfo($filename, PATHINFO_FILENAME);
            $created = $this->writeFile(
                $directory . DIRECTORY_SEPARATOR . $filename,
                $stubs->render($stub, [
                    'namespace' => $namespace,
                    'class' => $class,
                    'integration' => $integration,
                    'module' => $module,
                ]),
                $force,
            ) || $created;
        }

        return $created ? self::SUCCESS : self::FAILURE;
    }
}
