<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    public function test_it_creates_the_default_module_structure(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Domain/Entities')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Domain/ValueObjects')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Application/UseCases')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Application/Ports/In')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Application/Ports/Out')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Infrastructure/Http/Controllers')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Infrastructure/Persistence/Adapters')));
        $this->assertTrue($files->exists(base_path('app/Modules/Order/Infrastructure/Http/routes.php')));
        $this->assertFalse($files->isDirectory(base_path('app/Modules/Order/Domain/Contracts')));
    }

    public function test_it_respects_custom_module_paths(): void
    {
        config(['ddd.modules_path' => 'src/Modules']);

        $this->artisan('make:module', ['name' => 'Billing'])->assertSuccessful();

        $this->assertTrue((new Filesystem())->isDirectory(base_path('src/Modules/Billing/Domain/Entities')));
    }

    public function test_it_respects_minimal_preset(): void
    {
        $this->artisan('make:module', ['name' => 'User', '--preset' => 'minimal'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->isDirectory(base_path('app/Modules/User/Domain')));
        $this->assertFalse($files->isDirectory(base_path('app/Modules/User/Domain/Entities')));
    }

    public function test_it_respects_explicit_hexagonal_preset(): void
    {
        $this->artisan('make:module', ['name' => 'Order', '--preset' => 'hexagonal'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Application/Ports/In')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Application/Ports/Out')));
    }

    public function test_it_respects_tactical_preset(): void
    {
        $this->artisan('make:module', ['name' => 'Order', '--preset' => 'tactical'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Application/UseCases')));
        $this->assertFalse($files->isDirectory(base_path('app/Modules/Order/Application/Ports')));
    }

    public function test_it_respects_full_preset(): void
    {
        $this->artisan('make:module', ['name' => 'Order', '--preset' => 'full'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Infrastructure/Jobs')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Infrastructure/Listeners')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Infrastructure/Policies')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Infrastructure/Persistence/Repositories')));
    }

    public function test_it_rejects_module_names_with_path_traversal_segments(): void
    {
        $this->artisan('make:module', ['name' => '../../etc'])->assertFailed();

        $this->assertFalse((new Filesystem())->isDirectory(base_path('app/Modules/Etc')));
    }

    public function test_module_paths_rejects_unsafe_module_names(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ModulePaths(app()))->modulePath('../Billing');
    }

    public function test_make_domain_command_is_not_registered(): void
    {
        $this->assertArrayNotHasKey('make:domain', Artisan::all());
    }
}
