<?php

namespace SamuelNunes\LaravelDddToolkit;

use Illuminate\Support\ServiceProvider;
use SamuelNunes\LaravelDddToolkit\Commands\DddInstallCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeAclCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeAggregateCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeDomainCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeEntityCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeEventCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeListenerCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakePolicyCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeRepositoryCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeUsecaseCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeValueObjectCommand;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;

class LaravelDddToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ddd.php', 'ddd');

        $this->registerApplicationModulesProvider();
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/ddd.php' => $this->app->configPath('ddd.php'),
        ], 'ddd-config');

        $this->publishes([
            __DIR__ . '/../stubs' => $this->app->basePath('stubs/vendor/laravel-ddd-toolkit'),
        ], 'ddd-stubs');

        $this->commands([
            DddInstallCommand::class,
            MakeAclCommand::class,
            MakeAggregateCommand::class,
            MakeDomainCommand::class,
            MakeEntityCommand::class,
            MakeEventCommand::class,
            MakeListenerCommand::class,
            MakePolicyCommand::class,
            MakeRepositoryCommand::class,
            MakeUsecaseCommand::class,
            MakeValueObjectCommand::class,
        ]);
    }

    private function registerApplicationModulesProvider(): void
    {
        $providerClass = $this->applicationNamespace() . 'Providers\\ModulesServiceProvider';
        $providerPath = (new ModulePaths($this->app))->applicationPath('Providers/ModulesServiceProvider.php');

        if (! is_file($providerPath)) {
            return;
        }

        require_once $providerPath;

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    private function applicationNamespace(): string
    {
        try {
            return $this->app->getNamespace();
        } catch (\Throwable) {
            return 'App\\';
        }
    }

}
