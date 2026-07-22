<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeEntityCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:entity {module : The target module} {name : The entity class name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain entity inside a module.';

    protected function stubName(): string
    {
        return 'entity.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Domain\\Entities';
    }

    protected function relativeDirectory(): string
    {
        return 'Domain/Entities';
    }
}
