<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeValueObjectCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:value-object {name : The value object class name} {--module= : The target module} {--force : Overwrite existing files}';

    protected $description = 'Create a domain value object inside a module.';

    protected function stubName(): string
    {
        return 'value-object.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Domain\\ValueObjects';
    }

    protected function relativeDirectory(): string
    {
        return 'Domain/ValueObjects';
    }
}
