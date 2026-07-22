<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SamuelNunes\LaravelDddToolkit\Discovery\ModuleDiscovery;

class DddCacheCommand extends Command
{
    protected $signature = 'ddd:cache';

    protected $description = 'Cache discovered DDD modules, providers, and routes.';

    public function handle(Filesystem $files): int
    {
        $discovery = new ModuleDiscovery($this->laravel);
        $manifest = $discovery->buildManifest();
        $path = $discovery->manifestPath();
        $directory = dirname($path);

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }

        $files->put($path, "<?php\n\nreturn " . var_export($manifest, true) . ";\n");

        $this->components->info("DDD module manifest cached: {$path}");

        return self::SUCCESS;
    }
}
