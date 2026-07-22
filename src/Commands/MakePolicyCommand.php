<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakePolicyCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:policy {module : The target module} {name : The policy class name} {--force : Overwrite existing files even when policies are disabled}';

    protected $description = 'Create a policy inside a module infrastructure layer.';

    public function handle(\Illuminate\Filesystem\Filesystem $files): int
    {
        if (! (bool) config('ddd.create_policies', false) && ! (bool) $this->option('force')) {
            $this->components->error('Policy generation is disabled. Enable [ddd.create_policies] or pass [--force].');

            return self::FAILURE;
        }

        return parent::handle($files);
    }

    protected function stubName(): string
    {
        return 'policy.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Infrastructure\\Policies';
    }

    protected function relativeDirectory(): string
    {
        return 'Infrastructure/Policies';
    }
}
