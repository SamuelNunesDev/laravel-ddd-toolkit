<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class ModulesServiceProviderTest extends TestCase
{
    public function test_generated_provider_loads_module_routes(): void
    {
        $this->artisan('ddd:install')
            ->assertSuccessful();
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        (new Filesystem())->put(
            base_path('app/Modules/Order/Infrastructure/Http/routes.php'),
            "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/orders/ping', fn () => 'pong')->name('orders.ping');\n",
        );

        if (! class_exists(\App\Providers\ModulesServiceProvider::class)) {
            require_once base_path('app/Providers/ModulesServiceProvider.php');
        }

        (new \App\Providers\ModulesServiceProvider(app()))->boot();
        Route::getRoutes()->refreshNameLookups();

        $this->assertTrue(Route::has('orders.ping'));
    }

    public function test_generated_provider_registers_module_providers(): void
    {
        $this->artisan('ddd:install')
            ->assertSuccessful();
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        $providerPath = base_path('app/Modules/Order/Infrastructure/Providers/OrderServiceProvider.php');

        (new Filesystem())->put($providerPath, <<<'PHP'
<?php

namespace App\Modules\Order\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('order-provider-loaded', true);
    }
}
PHP);

        if (! class_exists(\App\Providers\ModulesServiceProvider::class)) {
            require_once base_path('app/Providers/ModulesServiceProvider.php');
        }

        (new \App\Providers\ModulesServiceProvider(app()))->register();

        $this->assertTrue(app('order-provider-loaded'));
    }
}
