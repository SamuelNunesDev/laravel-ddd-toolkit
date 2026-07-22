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
        $this->assertTrue($files->exists(base_path('AGENTS.md')));
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

    public function test_it_publishes_agents_file_when_missing(): void
    {
        $this->artisan('ddd:install')->assertSuccessful();

        $contents = (new Filesystem())->get(base_path('AGENTS.md'));

        $this->assertStringContainsString('Laravel DDD Toolkit', $contents);
        $this->assertStringContainsString('vertical modules', $contents);
        $this->assertStringContainsString('hexagonal architecture by default', $contents);
        $this->assertStringContainsString('make:module', $contents);
        $this->assertStringNotContainsString('php artisan make:domain', $contents);
    }

    public function test_it_does_not_overwrite_existing_agents_file(): void
    {
        $files = new Filesystem();
        $files->put(base_path('AGENTS.md'), "# Team instructions\n");

        $this->artisan('ddd:install')->assertSuccessful();

        $this->assertSame("# Team instructions\n", $files->get(base_path('AGENTS.md')));
    }

    public function test_no_agents_option_does_not_create_or_change_agents_file(): void
    {
        $files = new Filesystem();

        $this->artisan('ddd:install --no-agents')->assertSuccessful();
        $this->assertFalse($files->exists(base_path('AGENTS.md')));

        $files->put(base_path('AGENTS.md'), "# Team instructions\n");

        $this->artisan('ddd:install --no-agents')->assertSuccessful();

        $this->assertSame("# Team instructions\n", $files->get(base_path('AGENTS.md')));
    }

    public function test_merge_agents_creates_full_file_when_missing(): void
    {
        $this->artisan('ddd:install --merge-agents')->assertSuccessful();

        $contents = (new Filesystem())->get(base_path('AGENTS.md'));

        $this->assertStringContainsString('# AGENTS.md - Laravel DDD Toolkit', $contents);
        $this->assertStringContainsString('hexagonal architecture by default', $contents);
    }

    public function test_merge_agents_appends_managed_block_to_existing_file(): void
    {
        $files = new Filesystem();
        $files->put(base_path('AGENTS.md'), "# Team instructions\n\nKeep this.");

        $this->artisan('ddd:install --merge-agents')->assertSuccessful();

        $contents = $files->get(base_path('AGENTS.md'));

        $this->assertStringContainsString("# Team instructions\n\nKeep this.", $contents);
        $this->assertStringContainsString('<!-- BEGIN LARAVEL-DDD-TOOLKIT -->', $contents);
        $this->assertStringContainsString('<!-- END LARAVEL-DDD-TOOLKIT -->', $contents);
        $this->assertSame(1, substr_count($contents, '<!-- BEGIN LARAVEL-DDD-TOOLKIT -->'));
    }

    public function test_merge_agents_replaces_only_existing_managed_block(): void
    {
        $files = new Filesystem();
        $files->put(
            base_path('AGENTS.md'),
            "# Before\n\n<!-- BEGIN LARAVEL-DDD-TOOLKIT -->\nOld toolkit instructions.\n<!-- END LARAVEL-DDD-TOOLKIT -->\n\n# After\n",
        );

        $this->artisan('ddd:install --merge-agents')->assertSuccessful();

        $contents = $files->get(base_path('AGENTS.md'));

        $this->assertStringContainsString("# Before\n\n", $contents);
        $this->assertStringContainsString("\n\n# After\n", $contents);
        $this->assertStringContainsString('vertical modules under `app/Modules`', $contents);
        $this->assertStringNotContainsString('Old toolkit instructions.', $contents);
        $this->assertSame(1, substr_count($contents, '<!-- BEGIN LARAVEL-DDD-TOOLKIT -->'));
    }

    public function test_force_agents_overwrites_existing_file(): void
    {
        $files = new Filesystem();
        $files->put(base_path('AGENTS.md'), "# Team instructions\n");

        $this->artisan('ddd:install --force-agents')->assertSuccessful();

        $contents = $files->get(base_path('AGENTS.md'));

        $this->assertStringNotContainsString('Team instructions', $contents);
        $this->assertStringContainsString('# AGENTS.md - Laravel DDD Toolkit', $contents);
    }

    public function test_agents_disabled_config_prevents_publishing(): void
    {
        config(['ddd.agents.enabled' => false]);

        $this->artisan('ddd:install --force-agents')->assertSuccessful();

        $this->assertFalse((new Filesystem())->exists(base_path('AGENTS.md')));
    }

    public function test_publish_on_install_config_prevents_default_publishing(): void
    {
        config(['ddd.agents.publish_on_install' => false]);

        $this->artisan('ddd:install')->assertSuccessful();

        $this->assertFalse((new Filesystem())->exists(base_path('AGENTS.md')));
    }
}
