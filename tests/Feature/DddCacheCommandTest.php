<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class DddCacheCommandTest extends TestCase
{
    public function test_it_caches_and_clears_module_discovery_manifest(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        $this->artisan('ddd:cache')->assertSuccessful();

        $path = base_path('bootstrap/cache/ddd-modules.php');
        $manifest = require $path;

        $this->assertFileExists($path);
        $this->assertContains(base_path('app/Modules/Order'), $manifest['modules']);
        $this->assertContains(base_path('app/Modules/Order/Infrastructure/Http/routes.php'), $manifest['routes']);

        $this->artisan('ddd:clear')->assertSuccessful();

        $this->assertFileDoesNotExist($path);
    }

    public function test_generated_provider_loads_routes_from_cache_manifest(): void
    {
        $this->artisan('ddd:install')->assertSuccessful();
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        (new Filesystem())->put(
            base_path('app/Modules/Order/Infrastructure/Http/routes.php'),
            "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/orders/cached-ping', fn () => 'pong')->name('orders.cached-ping');\n",
        );

        $this->artisan('ddd:cache')->assertSuccessful();

        if (! class_exists(\App\Providers\ModulesServiceProvider::class)) {
            require_once base_path('app/Providers/ModulesServiceProvider.php');
        }

        (new \App\Providers\ModulesServiceProvider(app()))->boot();
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();

        $this->assertTrue(\Illuminate\Support\Facades\Route::has('orders.cached-ping'));
    }
}
