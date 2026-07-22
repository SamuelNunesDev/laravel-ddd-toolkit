<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeUsecaseCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:usecase {name : The use case class name} {--module= : The target module} {--force : Overwrite existing files}';

    protected $description = 'Create an application use case handler inside a module.';

    protected function stubName(): string
    {
        return 'usecase.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Application\\Handlers';
    }

    protected function relativeDirectory(): string
    {
        return 'Application/Handlers';
    }
}
