<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeEventCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:event {module : The target module} {name : The domain event class name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain event inside a module.';

    protected function stubName(): string
    {
        return 'event.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Domain\\Events';
    }

    protected function relativeDirectory(): string
    {
        return 'Domain/Events';
    }
}
