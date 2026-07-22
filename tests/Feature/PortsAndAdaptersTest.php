<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class PortsAndAdaptersTest extends TestCase
{
    public function test_it_creates_in_and_out_ports(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        $this->artisan('make:port', ['module' => 'Order', 'name' => 'CancelOrderUseCase', '--type' => 'in'])->assertSuccessful();
        $this->artisan('make:port', ['module' => 'Order', 'name' => 'OrderRepository', '--type' => 'out'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertStringContainsString(
            'namespace App\\Modules\\Order\\Application\\Ports\\In;',
            $files->get(base_path('app/Modules/Order/Application/Ports/In/CancelOrderUseCase.php')),
        );
        $this->assertStringContainsString(
            'interface CancelOrderUseCase',
            $files->get(base_path('app/Modules/Order/Application/Ports/In/CancelOrderUseCase.php')),
        );
        $this->assertStringContainsString(
            'namespace App\\Modules\\Order\\Application\\Ports\\Out;',
            $files->get(base_path('app/Modules/Order/Application/Ports/Out/OrderRepository.php')),
        );
    }

    public function test_it_creates_persistence_adapter_and_registers_binding(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();
        $this->artisan('make:port', ['module' => 'Order', 'name' => 'OrderRepository'])->assertSuccessful();

        $this->artisan('make:adapter', [
            'module' => 'Order',
            'name' => 'EloquentOrderRepository',
            '--port' => 'OrderRepository',
            '--type' => 'persistence',
        ])->assertSuccessful();

        $files = new Filesystem();
        $adapter = $files->get(base_path('app/Modules/Order/Infrastructure/Persistence/Adapters/EloquentOrderRepository.php'));
        $provider = $files->get(base_path('app/Modules/Order/Infrastructure/Providers/OrderServiceProvider.php'));

        $this->assertStringContainsString('use App\\Modules\\Order\\Application\\Ports\\Out\\OrderRepository;', $adapter);
        $this->assertStringContainsString('final class EloquentOrderRepository implements OrderRepository', $adapter);
        $this->assertSame(1, substr_count($provider, '\\App\\Modules\\Order\\Application\\Ports\\Out\\OrderRepository::class'));
        $this->assertStringContainsString('EloquentOrderRepository::class', $provider);

        $this->artisan('make:adapter', [
            'module' => 'Order',
            'name' => 'EloquentOrderRepository',
            '--port' => 'OrderRepository',
            '--type' => 'persistence',
            '--force' => true,
        ])->assertSuccessful();

        $provider = $files->get(base_path('app/Modules/Order/Infrastructure/Providers/OrderServiceProvider.php'));

        $this->assertSame(1, substr_count($provider, '\\App\\Modules\\Order\\Application\\Ports\\Out\\OrderRepository::class'));
    }

    public function test_it_creates_integration_adapter(): void
    {
        $this->artisan('make:module', ['name' => 'Payment'])->assertSuccessful();

        $this->artisan('make:adapter', [
            'module' => 'Payment',
            'name' => 'StripePaymentGateway',
            '--type' => 'integration',
        ])->assertSuccessful();

        $this->assertFileExists(base_path('app/Modules/Payment/Infrastructure/Integrations/Stripe/StripePaymentGateway.php'));
    }

    public function test_make_acl_command_is_not_registered(): void
    {
        $this->assertArrayNotHasKey('make:acl', Artisan::all());
    }
}
