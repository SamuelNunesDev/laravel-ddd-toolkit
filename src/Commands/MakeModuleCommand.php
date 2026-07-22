<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;
use SamuelNunes\LaravelDddToolkit\Commands\Concerns\WritesFiles;
use SamuelNunes\LaravelDddToolkit\Support\ModuleAiDocsFileResult;
use SamuelNunes\LaravelDddToolkit\Support\ModuleAiDocsGenerator;
use SamuelNunes\LaravelDddToolkit\Support\ModuleAiDocsResult;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Support\ModuleStructure;
use SamuelNunes\LaravelDddToolkit\Support\StubRenderer;

class MakeModuleCommand extends Command
{
    use WritesFiles;

    protected $signature = 'make:module
        {name : The module name}
        {--preset= : Structure preset: hexagonal, minimal, tactical, or full}
        {--force : Overwrite generated files}
        {--with-ai-docs : Generate module README.md and AGENTS.md files}
        {--no-ai-docs : Skip module README.md and AGENTS.md generation}
        {--context= : Business context for generated module AI docs}
        {--context-file= : File containing business context for generated module AI docs}';

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
            $context = $this->moduleContext($modulePaths);
            $preset = $this->preset();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $modulePath = $modulePaths->modulePath($module);
        $moduleAlreadyExists = $this->files->isDirectory($modulePath);

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

        $this->components->info(
            $moduleAlreadyExists
                ? "Module {$module} already exists."
                : "Module {$module} created.",
        );

        $this->generateAiDocs(
            new ModuleAiDocsGenerator($files, $stubs),
            $modulePath,
            $module,
            $context,
        );

        return self::SUCCESS;
    }

    private function moduleContext(ModulePaths $modulePaths): ?string
    {
        if ((bool) $this->option('no-ai-docs') || ! $this->shouldGenerateAiDocs()) {
            return null;
        }

        $contextFile = $this->option('context-file');

        if (is_string($contextFile) && trim($contextFile) !== '') {
            return $this->readContextFile($modulePaths, $contextFile);
        }

        $context = $this->option('context');

        if (is_string($context) && trim($context) !== '') {
            return $context;
        }

        if ($this->shouldAskForContext()) {
            $this->components->warn('Do not include secrets, credentials, access tokens or sensitive customer data.');

            $answer = $this->ask(
                "What business capability does this module represent?\n\n"
                . "Describe as much context as possible:\n"
                . "- purpose\n"
                . "- business boundaries\n"
                . "- important rules\n"
                . "- expected use cases\n"
                . "- main domain concepts\n"
                . "- expected integrations\n"
                . "- what this module must NOT do",
            );

            return is_string($answer) ? $answer : null;
        }

        return null;
    }

    private function readContextFile(ModulePaths $modulePaths, string $contextFile): string
    {
        $path = $modulePaths->absolutePath($contextFile);

        if (! $this->files->exists($path)) {
            throw new RuntimeException("Context file [{$contextFile}] was not found.");
        }

        return $this->files->get($path);
    }

    private function shouldAskForContext(): bool
    {
        return $this->input->isInteractive()
            && $this->stdinIsInteractive()
            && (bool) config('ddd.ai_docs.ask_context_on_module_creation', true);
    }

    private function stdinIsInteractive(): bool
    {
        if (! defined('STDIN') || ! function_exists('stream_isatty')) {
            return false;
        }

        return stream_isatty(STDIN);
    }

    private function shouldGenerateAiDocs(): bool
    {
        if ((bool) $this->option('no-ai-docs')) {
            return false;
        }

        if (! (bool) config('ddd.ai_docs.enabled', true)) {
            return false;
        }

        return (bool) $this->option('with-ai-docs')
            || (bool) config('ddd.ai_docs.module_readme.enabled', true)
            || (bool) config('ddd.ai_docs.module_agents.enabled', true);
    }

    private function generateAiDocs(
        ModuleAiDocsGenerator $generator,
        string $modulePath,
        string $module,
        ?string $context,
    ): void {
        if (! $this->shouldGenerateAiDocs()) {
            return;
        }

        $result = $generator->generate(
            $modulePath,
            $module,
            $context,
            force: (bool) $this->option('force'),
            enabled: (bool) config('ddd.ai_docs.enabled', true),
            readmeEnabled: (bool) config('ddd.ai_docs.module_readme.enabled', true),
            agentsEnabled: (bool) config('ddd.ai_docs.module_agents.enabled', true),
            readmeFilename: (string) config('ddd.ai_docs.module_readme.filename', 'README.md'),
            agentsFilename: (string) config('ddd.ai_docs.module_agents.filename', 'AGENTS.md'),
        );

        $this->reportAiDocsResult($result);
    }

    private function reportAiDocsResult(ModuleAiDocsResult $result): void
    {
        if ($result->usedPlaceholder) {
            $this->components->warn('No module context provided. Generated AI docs with placeholders.');
        }

        foreach ($result->files as $file) {
            $this->reportAiDocsFileResult($file);
        }
    }

    private function reportAiDocsFileResult(ModuleAiDocsFileResult $result): void
    {
        if ($result->status === 'exists') {
            $this->line('- ' . $result->message);

            return;
        }

        $this->components->info($result->message);
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
