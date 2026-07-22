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
            "<?php\n\nnamespace App\\Modules\\Order\\Domain\\Entities;\n\nuse Illuminate\\Database\\Eloquent\\Model as BaseModel;\n\nclass Order extends BaseModel\n{\n}\n",
        );

        $this->artisan('ddd:check')
            ->expectsOutputToContain('Domain layer imports Laravel Illuminate classes.')
            ->assertFailed();
    }

    public function test_it_ignores_forbidden_namespaces_inside_comments_and_strings(): void
    {
        $this->artisan('make:module', ['name' => 'Order'])->assertSuccessful();

        (new Filesystem())->put(
            base_path('app/Modules/Order/Domain/Entities/Order.php'),
            "<?php\n\nnamespace App\\Modules\\Order\\Domain\\Entities;\n\nclass Order\n{\n    public string \$example = 'Illuminate\\\\Database\\\\Eloquent\\\\Model';\n\n    // App\\Modules\\Order\\Infrastructure\\Persistence\\Models\\OrderModel\n}\n",
        );

        $this->artisan('ddd:check')->assertSuccessful();
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
