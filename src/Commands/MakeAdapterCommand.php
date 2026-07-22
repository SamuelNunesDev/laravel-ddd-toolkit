<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

class MakeAdapterCommand extends Command
{
    use WritesFiles;

    protected $signature = 'make:adapter {module : The target module} {name : The adapter class name} {--port= : Port interface implemented by this adapter} {--type=persistence : Adapter type: persistence or integration} {--force : Overwrite existing files}';

    protected $description = 'Create an infrastructure adapter and optionally bind it to an application port.';

    protected Filesystem $files;

    private ModulePaths $modulePaths;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $this->modulePaths = new ModulePaths($this->laravel);
        $stubs = new StubRenderer($this->laravel, $files);

        try {
            $module = $this->modulePaths->moduleName((string) $this->argument('module'));
            $type = $this->adapterType();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $files->isDirectory($this->modulePaths->modulePath($module))) {
            $this->components->error("Module [{$module}] does not exist. Run [php artisan make:module {$module}] first.");

            return self::FAILURE;
        }

        $class = Str::studly((string) $this->argument('name'));
        $relativeDirectory = $this->relativeDirectory($type, $class);
        $relativeNamespace = str_replace('/', '\\', $relativeDirectory);
        $namespace = $this->modulePaths->moduleNamespace($module) . '\\' . $relativeNamespace;
        $path = $this->modulePaths->modulePath($module) . DIRECTORY_SEPARATOR . $relativeDirectory . DIRECTORY_SEPARATOR . "{$class}.php";
        $port = $this->portClass();
        $portNamespace = $port !== null
            ? $this->modulePaths->moduleNamespace($module) . "\\Application\\Ports\\Out\\{$port}"
            : null;

        $created = $this->writeFile(
            $path,
            $stubs->render('adapter.stub', [
                'namespace' => $namespace,
                'class' => $class,
                'module' => $module,
                'port_use' => $portNamespace !== null ? "use {$portNamespace};" : '',
                'implements' => $port !== null ? " implements {$port}" : '',
            ]),
            (bool) $this->option('force'),
        );

        if ($port !== null) {
            $this->registerBinding($module, $portNamespace, $namespace . "\\{$class}");
        }

        return $created ? self::SUCCESS : self::FAILURE;
    }

    private function adapterType(): string
    {
        $type = strtolower((string) $this->option('type'));

        if (! in_array($type, ['persistence', 'integration'], true)) {
            throw new InvalidArgumentException("Invalid adapter type [{$type}]. Use [persistence] or [integration].");
        }

        return $type;
    }

    private function portClass(): ?string
    {
        $port = $this->option('port');

        return is_string($port) && $port !== '' ? Str::studly($port) : null;
    }

    private function relativeDirectory(string $type, string $class): string
    {
        if ($type === 'persistence') {
            return 'Infrastructure/Persistence/Adapters';
        }

        return 'Infrastructure/Integrations/' . $this->integrationName($class);
    }

    private function integrationName(string $class): string
    {
        $name = preg_replace('/(PaymentGateway|Gateway|Adapter|Client|Repository)$/', '', $class);

        return $name !== null && $name !== '' ? $name : $class;
    }

    private function registerBinding(string $module, string $portClass, string $adapterClass): void
    {
        $providerClass = "{$module}ServiceProvider";
        $providerNamespace = $this->modulePaths->moduleNamespace($module) . '\\Infrastructure\\Providers';
        $providerPath = $this->modulePaths->modulePath($module) . DIRECTORY_SEPARATOR . "Infrastructure/Providers/{$providerClass}.php";
        $binding = "\$this->app->bind(\n            \\{$portClass}::class,\n            \\{$adapterClass}::class,\n        );";

        if (! $this->files->exists($providerPath)) {
            $contents = "<?php\n\nnamespace {$providerNamespace};\n\nuse Illuminate\\Support\\ServiceProvider;\n\nclass {$providerClass} extends ServiceProvider\n{\n    public function register(): void\n    {\n        {$binding}\n    }\n}\n";
            $this->writeFile($providerPath, $contents, false);

            return;
        }

        $contents = $this->files->get($providerPath);

        if (str_contains($contents, $portClass . '::class') && str_contains($contents, $adapterClass . '::class')) {
            $this->components->info("Binding already exists in {$providerPath}.");

            return;
        }

        $updated = preg_replace_callback(
            '/public function register\(\): void\s*\{\n/',
            static fn (array $matches): string => $matches[0] . "        {$binding}\n",
            $contents,
            1,
        );

        if ($updated === null || $updated === $contents) {
            $this->components->warn("Could not update {$providerPath}. Register the binding manually.");

            return;
        }

        $this->files->put($providerPath, $updated);
        $this->components->info("Registered binding in {$providerPath}.");
    }
}
