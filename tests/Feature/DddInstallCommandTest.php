<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class DddInstallCommandTest extends TestCase
{
    public function test_it_installs_the_base_structure(): void
    {
        $this->artisan('ddd:install')
            ->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->isDirectory(base_path('app/Modules')));
        $this->assertTrue($files->isDirectory(base_path('app/Shared')));
        $this->assertTrue($files->exists(base_path('app/Providers/ModulesServiceProvider.php')));
        $this->assertTrue($files->exists(config_path('ddd.php')));
        $this->assertStringContainsString(
            'App\\Providers\\ModulesServiceProvider::class',
            $files->get(base_path('bootstrap/providers.php')),
        );
    }

    public function test_it_is_idempotent(): void
    {
        $this->artisan('ddd:install')->assertSuccessful();
        $this->artisan('ddd:install')->assertSuccessful();

        $providers = (new Filesystem())->get(base_path('bootstrap/providers.php'));

        $this->assertSame(1, substr_count($providers, 'App\\Providers\\ModulesServiceProvider::class'));
    }
}
