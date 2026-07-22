<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SamuelNunes\LaravelDddToolkit\Support\AgentsFilePublisher;
use SamuelNunes\LaravelDddToolkit\Support\AgentsPublishResult;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

class DddInstallCommand extends Command
{
    use WritesFiles;

    protected $signature = 'ddd:install
        {--force : Overwrite generated files}
        {--no-agents : Skip AGENTS.md publishing}
        {--merge-agents : Append or update Laravel DDD Toolkit instructions in AGENTS.md}
        {--force-agents : Overwrite AGENTS.md with Laravel DDD Toolkit instructions}';

    protected $description = 'Install the Laravel DDD Toolkit structure in the application.';

    protected Filesystem $files;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $modulePaths = new ModulePaths($this->laravel);
        $stubs = new StubRenderer($this->laravel, $files);
        $force = (bool) $this->option('force');

        $this->ensureDirectoryExists($modulePaths->modulesPath());
        $this->ensureDirectoryExists($modulePaths->sharedPath());
        $this->ensureDirectoryExists($modulePaths->applicationPath('Providers'));

        $this->publishConfig($force);
        $this->createModulesServiceProvider($modulePaths, $stubs, $force);
        $this->registerModulesServiceProvider($modulePaths);
        $this->publishAgentsFile(new AgentsFilePublisher($files, $stubs));
        $this->reportLegacyFolders();

        $this->components->info('Laravel DDD Toolkit installed.');

        return self::SUCCESS;
    }

    private function publishConfig(bool $force): void
    {
        $source = __DIR__ . '/../../config/ddd.php';
        $target = $this->laravel->configPath('ddd.php');

        if ($this->files->exists($target) && ! $force) {
            $this->components->warn("Skipped existing config: {$target}");

            return;
        }

        $this->writeFile($target, $this->files->get($source), true);
    }

    private function createModulesServiceProvider(ModulePaths $modulePaths, StubRenderer $stubs, bool $force): void
    {
        $path = $modulePaths->applicationPath('Providers/ModulesServiceProvider.php');
        $contents = $stubs->render('modules-service-provider.stub', [
            'namespace' => rtrim($modulePaths->applicationNamespace(), '\\') . '\\Providers',
            'class' => 'ModulesServiceProvider',
        ]);

        $this->writeFile($path, $contents, $force);
    }

    private function registerModulesServiceProvider(ModulePaths $modulePaths): void
    {
        $providersPath = $this->laravel->basePath('bootstrap/providers.php');

        if (! $this->files->exists($providersPath)) {
            $this->components->warn("Could not find {$providersPath}. Register ModulesServiceProvider manually.");

            return;
        }

        $provider = rtrim($modulePaths->applicationNamespace(), '\\') . '\\Providers\\ModulesServiceProvider::class';
        $contents = $this->files->get($providersPath);

        if (str_contains($contents, $provider)) {
            $this->components->info('ModulesServiceProvider is already registered.');

            return;
        }

        $replacement = "    {$provider},\n];";
        $updated = preg_replace('/\];\s*$/', $replacement, $contents, 1);

        if ($updated === null) {
            $this->components->error("Could not parse {$providersPath}. Register {$provider} manually.");

            return;
        }

        if ($updated === $contents) {
            $this->components->warn("Could not find an insertion point in {$providersPath}. Register {$provider} manually.");

            return;
        }

        $this->files->put($providersPath, $updated);
        $this->components->info('ModulesServiceProvider registered in bootstrap/providers.php.');
    }

    private function publishAgentsFile(AgentsFilePublisher $publisher): void
    {
        $result = $publisher->publish(
            $this->laravel->basePath(),
            merge: (bool) $this->option('merge-agents'),
            force: (bool) $this->option('force-agents'),
            skip: (bool) $this->option('no-agents'),
            enabled: (bool) config('ddd.agents.enabled', true),
            publishOnInstall: (bool) config('ddd.agents.publish_on_install', true),
            filename: (string) config('ddd.agents.filename', 'AGENTS.md'),
        );

        $this->reportAgentsPublishResult($result);
    }

    private function reportAgentsPublishResult(AgentsPublishResult $result): void
    {
        if ($result->status === 'exists') {
            $this->components->warn($result->message);

            return;
        }

        if ($result->status === 'skipped') {
            $this->line('- ' . $result->message);

            return;
        }

        $this->components->info($result->message);
    }

    private function reportLegacyFolders(): void
    {
        $modulePaths = new ModulePaths($this->laravel);

        $legacyFolders = array_filter([
            $modulePaths->applicationPath('Models'),
            $modulePaths->applicationPath('Services'),
            $modulePaths->applicationPath('Repositories'),
        ], fn (string $path): bool => $this->files->isDirectory($path));

        if ($legacyFolders === []) {
            return;
        }

        if (! $this->confirm('Legacy Laravel folders were found. Do you want to review them for manual removal?', false)) {
            return;
        }

        $this->components->warn('No folders were removed automatically. Review and remove them manually if they are no longer used:');

        foreach ($legacyFolders as $folder) {
            $this->line("- {$folder}");
        }
    }

}
