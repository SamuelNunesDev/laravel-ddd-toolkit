<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeListenerCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:listener {module : The target module} {name : The listener class name} {--force : Overwrite existing files}';

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
