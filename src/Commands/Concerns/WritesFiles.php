<?php

namespace SamuelNunes\LaravelDddToolkit\Commands\Concerns;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait WritesFiles
{
    protected function ensureDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    protected function writeFile(string $path, string $contents, bool $force = false): bool
    {
        if ($this->files->exists($path) && ! $force) {
            $this->components->warn("Skipped existing file: {$path}");

            return false;
        }

        $directory = dirname($path);

        $this->ensureDirectoryExists($directory);

        if (! is_writable($directory)) {
            $this->components->error("Directory is not writable: {$directory}");

            return false;
        }

        $this->files->put($path, $contents);
        $this->components->info("Created: {$path}");

        return true;
    }
}
