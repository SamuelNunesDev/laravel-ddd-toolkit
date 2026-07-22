<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

use Illuminate\Filesystem\Filesystem;

class AgentsFilePublisher
{
    public const BEGIN_MARKER = '<!-- BEGIN LARAVEL-DDD-TOOLKIT -->';

    public const END_MARKER = '<!-- END LARAVEL-DDD-TOOLKIT -->';

    public function __construct(
        private readonly Filesystem $files,
        private readonly StubRenderer $stubs,
    ) {
    }

    public function publish(
        string $projectRoot,
        bool $merge = false,
        bool $force = false,
        bool $skip = false,
        bool $enabled = true,
        bool $publishOnInstall = true,
        string $filename = 'AGENTS.md',
    ): AgentsPublishResult {
        if ($skip) {
            return new AgentsPublishResult('skipped', 'Skipped AGENTS.md publishing.');
        }

        if (! $enabled) {
            return new AgentsPublishResult('skipped', 'Skipped AGENTS.md publishing because agents support is disabled.');
        }

        if (! $publishOnInstall && ! $merge && ! $force) {
            return new AgentsPublishResult('skipped', 'Skipped AGENTS.md publishing because publish_on_install is disabled.');
        }

        if ($force) {
            return $this->force($projectRoot, $filename);
        }

        if ($merge) {
            return $this->merge($projectRoot, $filename);
        }

        return $this->createIfMissing($projectRoot, $filename);
    }

    private function createIfMissing(string $projectRoot, string $filename): AgentsPublishResult
    {
        $path = $this->path($projectRoot, $filename);
        $filename = $this->filename($filename);

        if ($this->files->exists($path)) {
            return new AgentsPublishResult(
                'exists',
                "{$filename} already exists. Skipped to avoid overwriting project instructions.\n"
                    . 'Use --merge-agents to append/update Laravel DDD Toolkit instructions.' . "\n"
                    . 'Use --force-agents to overwrite the file.',
            );
        }

        $this->write($path, $this->fullStub());

        return new AgentsPublishResult('published', "Published {$filename}");
    }

    private function merge(string $projectRoot, string $filename): AgentsPublishResult
    {
        $path = $this->path($projectRoot, $filename);
        $filename = $this->filename($filename);

        if (! $this->files->exists($path)) {
            $this->write($path, $this->fullStub());

            return new AgentsPublishResult('published', "Published {$filename}");
        }

        $contents = $this->files->get($path);
        $block = $this->blockStub();

        if ($this->hasManagedBlock($contents)) {
            $this->files->put($path, $this->replaceManagedBlock($contents, $block));

            return new AgentsPublishResult('merged', "Updated Laravel DDD Toolkit instructions in {$filename}");
        }

        $separator = str_ends_with($contents, "\n") ? "\n" : "\n\n";

        $this->files->put($path, $contents . $separator . $block);

        return new AgentsPublishResult('merged', "Merged Laravel DDD Toolkit instructions into {$filename}");
    }

    private function force(string $projectRoot, string $filename): AgentsPublishResult
    {
        $this->write($this->path($projectRoot, $filename), $this->fullStub());

        return new AgentsPublishResult('overwritten', 'Overwritten ' . $this->filename($filename));
    }

    private function hasManagedBlock(string $contents): bool
    {
        return str_contains($contents, self::BEGIN_MARKER)
            && str_contains($contents, self::END_MARKER);
    }

    private function replaceManagedBlock(string $contents, string $block): string
    {
        $pattern = '/'
            . preg_quote(self::BEGIN_MARKER, '/')
            . '.*?'
            . preg_quote(self::END_MARKER, '/')
            . '/s';

        $updated = preg_replace($pattern, $block, $contents, 1);

        return $updated ?? $contents;
    }

    private function write(string $path, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
    }

    private function path(string $projectRoot, string $filename): string
    {
        return rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->filename($filename);
    }

    private function filename(string $filename): string
    {
        $filename = basename($filename);

        return $filename === '' ? 'AGENTS.md' : $filename;
    }

    private function fullStub(): string
    {
        return $this->stubs->render('agents/AGENTS.md.stub', []);
    }

    private function blockStub(): string
    {
        return $this->stubs->render('agents/AGENTS.block.md.stub', []);
    }
}
