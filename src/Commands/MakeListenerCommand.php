<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeListenerCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:listener {name : The listener class name} {--module= : The target module} {--force : Overwrite existing files}';

    protected $description = 'Create a Laravel listener inside a module infrastructure layer.';

    protected function stubName(): string
    {
        return 'listener.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Infrastructure\\Listeners';
    }

    protected function relativeDirectory(): string
    {
        return 'Infrastructure/Listeners';
    }
}
