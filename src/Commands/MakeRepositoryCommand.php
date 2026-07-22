<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeRepositoryCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:repository {name : The repository class name} {--module= : The target module} {--force : Overwrite existing files even when repositories are disabled}';

    protected $description = 'Create an optional repository inside a module infrastructure layer.';

    public function handle(\Illuminate\Filesystem\Filesystem $files): int
    {
        if (! (bool) config('ddd.create_repositories', false) && ! (bool) $this->option('force')) {
            $this->components->error('Repository generation is disabled. Enable [ddd.create_repositories] or pass [--force].');

            return self::FAILURE;
        }

        return parent::handle($files);
    }

    protected function stubName(): string
    {
        return 'repository.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Infrastructure\\Persistence\\Repositories';
    }

    protected function relativeDirectory(): string
    {
        return 'Infrastructure/Persistence/Repositories';
    }
}
