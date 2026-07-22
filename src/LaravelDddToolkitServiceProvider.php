<?php

namespace SamuelNunes\LaravelDddToolkit;

use Illuminate\Support\ServiceProvider;
use SamuelNunes\LaravelDddToolkit\Commands\DddInstallCommand;
use SamuelNunes\LaravelDddToolkit\Commands\DddCacheCommand;
use SamuelNunes\LaravelDddToolkit\Commands\DddCheckCommand;
use SamuelNunes\LaravelDddToolkit\Commands\DddClearCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeAdapterCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeAggregateCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeModuleCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeEntityCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeEventCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeIntegrationCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakeListenerCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakePolicyCommand;
use SamuelNunes\LaravelDddToolkit\Commands\MakePortCommand;
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
            DddCacheCommand::class,
            DddCheckCommand::class,
            DddClearCommand::class,
            DddInstallCommand::class,
            MakeAdapterCommand::class,
            MakeAggregateCommand::class,
            MakeModuleCommand::class,
            MakeEntityCommand::class,
            MakeEventCommand::class,
            MakeIntegrationCommand::class,
            MakeListenerCommand::class,
            MakePolicyCommand::class,
            MakePortCommand::class,
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
