<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModuleAiDocsGenerator
{
    private const PLACEHOLDER_CONTEXT = 'TODO: Add business context for this module.';

    public function __construct(
        private readonly Filesystem $files,
        private readonly StubRenderer $stubs,
    ) {
    }

    public function generate(
        string $modulePath,
        string $module,
        ?string $context = null,
        bool $force = false,
        bool $enabled = true,
        bool $readmeEnabled = true,
        bool $agentsEnabled = true,
        string $readmeFilename = 'README.md',
        string $agentsFilename = 'AGENTS.md',
    ): ModuleAiDocsResult {
        if (! $enabled) {
            return new ModuleAiDocsResult([]);
        }

        $context = $this->normalizeContext($context);
        $replacements = $this->replacements($module, $context);
        $results = [];

        if ($readmeEnabled) {
            $results[] = $this->writeDoc(
                $modulePath,
                $this->filename($readmeFilename, 'README.md'),
                $this->stubs->render('module/README.md.stub', $replacements),
                $force,
            );
        }

        if ($agentsEnabled) {
            $results[] = $this->writeDoc(
                $modulePath,
                $this->filename($agentsFilename, 'AGENTS.md'),
                $this->stubs->render('module/AGENTS.md.stub', $replacements),
                $force,
            );
        }

        return new ModuleAiDocsResult($results, $context === self::PLACEHOLDER_CONTEXT);
    }

    /**
     * @return array<string, string>
     */
    private function replacements(string $module, string $context): array
    {
        return [
            'module' => $module,
            'moduleStudly' => Str::studly($module),
            'moduleKebab' => Str::kebab($module),
            'moduleSnake' => Str::snake($module),
            'context' => $context,
            'generatedAt' => date('c'),
        ];
    }

    private function writeDoc(string $modulePath, string $filename, string $contents, bool $force): ModuleAiDocsFileResult
    {
        $path = rtrim($modulePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if ($this->files->exists($path) && ! $force) {
            return new ModuleAiDocsFileResult('exists', "{$filename} already exists. Skipped.");
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        return new ModuleAiDocsFileResult($force ? 'overwritten' : 'created', "Module {$filename} created.");
    }

    private function normalizeContext(?string $context): string
    {
        $context = trim((string) $context);

        return $context === '' ? self::PLACEHOLDER_CONTEXT : $context;
    }

    private function filename(string $filename, string $fallback): string
    {
        $filename = basename($filename);

        return $filename === '' ? $fallback : $filename;
    }
}
