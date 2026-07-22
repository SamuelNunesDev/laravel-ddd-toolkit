<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
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
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Application/Handlers')));
        $this->assertTrue($files->isDirectory(base_path('app/Modules/Order/Infrastructure/Http/Controllers')));
        $this->assertTrue($files->exists(base_path('app/Modules/Order/Infrastructure/Http/routes.php')));
    }

    public function test_it_respects_custom_module_paths(): void
    {
        config(['ddd.modules_path' => 'src/Modules']);

        $this->artisan('make:module', ['name' => 'Billing'])->assertSuccessful();

        $this->assertTrue((new Filesystem())->isDirectory(base_path('src/Modules/Billing/Domain/Entities')));
    }

    public function test_it_respects_minimal_preset(): void
    {
        config(['ddd.preset' => 'minimal']);

        $this->artisan('make:module', ['name' => 'User'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->isDirectory(base_path('app/Modules/User/Domain')));
        $this->assertFalse($files->isDirectory(base_path('app/Modules/User/Domain/Entities')));
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
}
