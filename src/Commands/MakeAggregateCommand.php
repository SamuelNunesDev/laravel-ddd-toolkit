<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

class MakeAggregateCommand extends AbstractClassGeneratorCommand
{
    protected $signature = 'make:aggregate {name : The aggregate class name} {--module= : The target module} {--force : Overwrite existing files}';

    protected $description = 'Create a domain aggregate inside a module.';

    protected function stubName(): string
    {
        return 'aggregate.stub';
    }

    protected function relativeNamespace(): string
    {
        return 'Domain\\Aggregates';
    }

    protected function relativeDirectory(): string
    {
        return 'Domain/Aggregates';
    }
}
