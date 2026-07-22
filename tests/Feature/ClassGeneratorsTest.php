<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class ClassGeneratorsTest extends TestCase
{
    public function test_it_generates_mvp_classes_in_the_expected_namespaces(): void
    {
        $this->artisan('make:domain', ['name' => 'Order'])->assertSuccessful();
        $this->artisan('make:entity', ['name' => 'Order', '--module' => 'Order'])->assertSuccessful();
        $this->artisan('make:value-object', ['name' => 'Email', '--module' => 'Order'])->assertSuccessful();
        $this->artisan('make:usecase', ['name' => 'CancelOrder', '--module' => 'Order'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertStringContainsString(
            'namespace App\\Modules\\Order\\Domain\\Entities;',
            $files->get(base_path('app/Modules/Order/Domain/Entities/Order.php')),
        );
        $this->assertStringContainsString(
            'namespace App\\Modules\\Order\\Domain\\ValueObjects;',
            $files->get(base_path('app/Modules/Order/Domain/ValueObjects/Email.php')),
        );
        $this->assertStringContainsString(
            'namespace App\\Modules\\Order\\Application\\Handlers;',
            $files->get(base_path('app/Modules/Order/Application/Handlers/CancelOrder.php')),
        );
    }

    public function test_it_does_not_overwrite_without_force(): void
    {
        $this->artisan('make:domain', ['name' => 'Order'])->assertSuccessful();
        $this->artisan('make:entity', ['name' => 'Order', '--module' => 'Order', '--force' => true])->assertSuccessful();

        $path = base_path('app/Modules/Order/Domain/Entities/Order.php');
        (new Filesystem())->put($path, 'custom contents');

        $this->artisan('make:entity', ['name' => 'Order', '--module' => 'Order'])->assertFailed();

        $this->assertSame('custom contents', (new Filesystem())->get($path));
    }

    public function test_repository_generation_is_disabled_by_default(): void
    {
        $this->artisan('make:domain', ['name' => 'Order'])->assertSuccessful();

        $this->artisan('make:repository', ['name' => 'OrderRepository', '--module' => 'Order'])->assertFailed();

        $this->assertFalse((new Filesystem())->exists(base_path('app/Modules/Order/Infrastructure/Persistence/Repositories/OrderRepository.php')));
    }

    public function test_advanced_generators_create_expected_files(): void
    {
        config([
            'ddd.create_repositories' => true,
            'ddd.create_policies' => true,
        ]);

        $this->artisan('make:domain', ['name' => 'Payment'])->assertSuccessful();
        $this->artisan('make:acl', ['name' => 'Stripe', '--module' => 'Payment'])->assertSuccessful();
        $this->artisan('make:event', ['name' => 'OrderCancelled', '--module' => 'Payment'])->assertSuccessful();
        $this->artisan('make:listener', ['name' => 'RefundPayment', '--module' => 'Payment'])->assertSuccessful();
        $this->artisan('make:policy', ['name' => 'PaymentPolicy', '--module' => 'Payment'])->assertSuccessful();
        $this->artisan('make:repository', ['name' => 'PaymentRepository', '--module' => 'Payment'])->assertSuccessful();
        $this->artisan('make:aggregate', ['name' => 'PaymentAggregate', '--module' => 'Payment'])->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Infrastructure/Integrations/Stripe/Client.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Infrastructure/Integrations/Stripe/Adapter.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Infrastructure/Integrations/Stripe/Mapper.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Infrastructure/Integrations/Stripe/DTO.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Domain/Events/OrderCancelled.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Infrastructure/Listeners/RefundPayment.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Infrastructure/Policies/PaymentPolicy.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Infrastructure/Persistence/Repositories/PaymentRepository.php')));
        $this->assertTrue($files->exists(base_path('app/Modules/Payment/Domain/Aggregates/PaymentAggregate.php')));
    }

    public function test_acl_generation_completes_missing_files_without_overwriting_existing_files(): void
    {
        $this->artisan('make:domain', ['name' => 'Payment'])->assertSuccessful();
        $this->artisan('make:acl', ['name' => 'Stripe', '--module' => 'Payment'])->assertSuccessful();

        $files = new Filesystem();
        $directory = base_path('app/Modules/Payment/Infrastructure/Integrations/Stripe');
        $clientPath = $directory . '/Client.php';

        $files->put($clientPath, 'custom client');
        $files->delete($directory . '/Adapter.php');
        $files->delete($directory . '/Mapper.php');
        $files->delete($directory . '/DTO.php');

        $this->artisan('make:acl', ['name' => 'Stripe', '--module' => 'Payment'])->assertSuccessful();

        $this->assertSame('custom client', $files->get($clientPath));
        $this->assertTrue($files->exists($directory . '/Adapter.php'));
        $this->assertTrue($files->exists($directory . '/Mapper.php'));
        $this->assertTrue($files->exists($directory . '/DTO.php'));
    }
}
