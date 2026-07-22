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
        $this->assertTrue($files->exists(base_path('app/Modules/Order/README.md')));
        $this->assertTrue($files->exists(base_path('app/Modules/Order/AGENTS.md')));
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

    public function test_it_generates_module_ai_docs_by_default(): void
    {
        $this->artisan('make:module Billing')->assertSuccessful();

        $files = new Filesystem();
        $readme = $files->get(base_path('app/Modules/Billing/README.md'));
        $agents = $files->get(base_path('app/Modules/Billing/AGENTS.md'));

        foreach ([$readme, $agents] as $contents) {
            $this->assertStringContainsString('Billing', $contents);
            $this->assertStringContainsString('hexagonal architecture by default', $contents);
            $this->assertStringContainsString('Application/Ports/In', $contents);
            $this->assertStringContainsString('Application/Ports/Out', $contents);
            $this->assertStringContainsString('Infrastructure/Persistence/Adapters', $contents);
        }
    }

    public function test_it_uses_context_option_in_module_ai_docs(): void
    {
        $context = 'Handles invoice issuing, cancellation and invoice queries.';

        $this->artisan('make:module Billing --context="' . $context . '"')->assertSuccessful();

        $files = new Filesystem();

        $this->assertStringContainsString($context, $files->get(base_path('app/Modules/Billing/README.md')));
        $this->assertStringContainsString($context, $files->get(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_it_uses_context_file_in_module_ai_docs(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(base_path('docs'));
        $files->put(base_path('docs/billing-context.md'), "Billing owns invoice lifecycle.\n");

        $this->artisan('make:module Billing --context-file=docs/billing-context.md')->assertSuccessful();

        $this->assertStringContainsString(
            'Billing owns invoice lifecycle.',
            $files->get(base_path('app/Modules/Billing/README.md')),
        );
        $this->assertStringContainsString(
            'Billing owns invoice lifecycle.',
            $files->get(base_path('app/Modules/Billing/AGENTS.md')),
        );
    }

    public function test_it_fails_when_context_file_does_not_exist(): void
    {
        $this->artisan('make:module Billing --context-file=docs/missing.md')
            ->expectsOutputToContain('Context file [docs/missing.md] was not found.')
            ->assertFailed();

        $this->assertFalse((new Filesystem())->isDirectory(base_path('app/Modules/Billing')));
    }

    public function test_no_ai_docs_option_skips_module_ai_docs(): void
    {
        $this->artisan('make:module Billing --no-ai-docs')->assertSuccessful();

        $files = new Filesystem();

        $this->assertFalse($files->exists(base_path('app/Modules/Billing/README.md')));
        $this->assertFalse($files->exists(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_with_ai_docs_generates_docs_when_context_prompt_is_disabled(): void
    {
        config(['ddd.ai_docs.ask_context_on_module_creation' => false]);

        $this->artisan('make:module Billing --with-ai-docs')->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->exists(base_path('app/Modules/Billing/README.md')));
        $this->assertTrue($files->exists(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_ai_docs_config_can_disable_all_module_ai_docs(): void
    {
        config(['ddd.ai_docs.enabled' => false]);

        $this->artisan('make:module Billing')->assertSuccessful();

        $files = new Filesystem();

        $this->assertFalse($files->exists(base_path('app/Modules/Billing/README.md')));
        $this->assertFalse($files->exists(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_module_readme_config_can_disable_only_readme(): void
    {
        config(['ddd.ai_docs.module_readme.enabled' => false]);

        $this->artisan('make:module Billing')->assertSuccessful();

        $files = new Filesystem();

        $this->assertFalse($files->exists(base_path('app/Modules/Billing/README.md')));
        $this->assertTrue($files->exists(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_module_agents_config_can_disable_only_agents_file(): void
    {
        config(['ddd.ai_docs.module_agents.enabled' => false]);

        $this->artisan('make:module Billing')->assertSuccessful();

        $files = new Filesystem();

        $this->assertTrue($files->exists(base_path('app/Modules/Billing/README.md')));
        $this->assertFalse($files->exists(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_it_does_not_overwrite_existing_module_ai_docs_without_force(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(base_path('app/Modules/Billing'));
        $files->put(base_path('app/Modules/Billing/README.md'), "# Existing README\n");
        $files->put(base_path('app/Modules/Billing/AGENTS.md'), "# Existing AGENTS\n");

        $this->artisan('make:module Billing')->assertSuccessful();

        $this->assertSame("# Existing README\n", $files->get(base_path('app/Modules/Billing/README.md')));
        $this->assertSame("# Existing AGENTS\n", $files->get(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_force_overwrites_existing_module_ai_docs(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(base_path('app/Modules/Billing'));
        $files->put(base_path('app/Modules/Billing/README.md'), "# Existing README\n");
        $files->put(base_path('app/Modules/Billing/AGENTS.md'), "# Existing AGENTS\n");

        $this->artisan('make:module Billing --force')->assertSuccessful();

        $this->assertStringNotContainsString('Existing README', $files->get(base_path('app/Modules/Billing/README.md')));
        $this->assertStringNotContainsString('Existing AGENTS', $files->get(base_path('app/Modules/Billing/AGENTS.md')));
    }

    public function test_non_interactive_module_creation_generates_placeholder_docs(): void
    {
        $this->artisan('make:module Billing --no-interaction')
            ->expectsOutputToContain('No module context provided. Generated AI docs with placeholders.')
            ->assertSuccessful();

        $files = new Filesystem();

        $this->assertStringContainsString(
            'TODO: Add business context for this module.',
            $files->get(base_path('app/Modules/Billing/README.md')),
        );
        $this->assertStringContainsString(
            'TODO: Add business context for this module.',
            $files->get(base_path('app/Modules/Billing/AGENTS.md')),
        );
    }
}
