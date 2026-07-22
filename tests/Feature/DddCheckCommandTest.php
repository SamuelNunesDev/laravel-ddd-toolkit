<?php

namespace SamuelNunes\LaravelDddToolkit\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use SamuelNunes\LaravelDddToolkit\Tests\TestCase;

class DddCheckCommandTest extends TestCase
{
    public function test_it_passes_when_no_hexagonal_violations_exist(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        $this->artisan('ddd:check')->assertSuccessful();
    }

    public function test_it_detects_domain_violations(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        (new Filesystem())->put(
            base_path('app/Modules/Order/Domain/Entities/Order.php'),
            "<?php\n\nnamespace App\\Modules\\Order\\Domain\\Entities;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass Order extends Model\n{\n}\n",
        );

        $this->artisan('ddd:check')
            ->expectsOutputToContain('Domain layer imports Laravel Illuminate classes.')
            ->assertFailed();
    }

    public function test_it_detects_application_violations_for_a_single_module(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        (new Filesystem())->put(
            base_path('app/Modules/Order/Application/UseCases/CancelOrder.php'),
            "<?php\n\nnamespace App\\Modules\\Order\\Application\\UseCases;\n\nuse App\\Modules\\Order\\Infrastructure\\Persistence\\Models\\OrderModel;\n\nclass CancelOrder\n{\n}\n",
        );

        $this->artisan('ddd:check', ['--module' => 'Order'])
            ->expectsOutputToContain('Application layer imports persistence models.')
            ->assertFailed();
    }
}
