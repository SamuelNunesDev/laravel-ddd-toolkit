<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SamuelNunes\LaravelDddToolkit\Discovery\ModuleDiscovery;

class DddClearCommand extends Command
{
    protected $signature = 'ddd:clear';

    protected $description = 'Clear the cached DDD module manifest.';

    public function handle(Filesystem $files): int
    {
        $path = (new ModuleDiscovery($this->laravel))->manifestPath();

        if ($files->exists($path)) {
            $files->delete($path);
            $this->components->info("DDD module manifest cleared: {$path}");

            return self::SUCCESS;
        }

        $this->components->info('No DDD module manifest found.');

        return self::SUCCESS;
    }
}
