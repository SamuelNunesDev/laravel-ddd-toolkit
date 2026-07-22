<?php

namespace SamuelNunes\LaravelDddToolkit\Tests;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;
use SamuelNunes\LaravelDddToolkit\LaravelDddToolkitServiceProvider;

abstract class TestCase extends Orchestra
{
    public static function applicationBasePath()
    {
        return sys_get_temp_dir() . '/laravel-ddd-toolkit-tests/' . str_replace('\\', '_', static::class);
    }

    protected function setUp(): void
    {
        $this->resetApplicationBasePath();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetApplicationBasePath();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDddToolkitServiceProvider::class,
        ];
    }

    protected function resetApplicationBasePath(): void
    {
        $files = new Filesystem();
        $basePath = static::applicationBasePath();

        $files->delete(dirname(__DIR__) . '/vendor/orchestra/testbench-core/laravel/config/ddd.php');
        $files->deleteDirectory($basePath . '/app');
        $files->deleteDirectory($basePath . '/config');
        $files->deleteDirectory($basePath . '/stubs');
        $files->delete($basePath . '/AGENTS.md');
        $files->delete($basePath . '/bootstrap/cache/ddd-modules.php');
        $files->ensureDirectoryExists($basePath . '/bootstrap');
        $files->ensureDirectoryExists($basePath . '/bootstrap/cache');
        $files->ensureDirectoryExists($basePath . '/storage/framework/cache');
        $files->ensureDirectoryExists($basePath . '/storage/framework/sessions');
        $files->ensureDirectoryExists($basePath . '/storage/framework/views');
        $files->put($basePath . '/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    }
}
