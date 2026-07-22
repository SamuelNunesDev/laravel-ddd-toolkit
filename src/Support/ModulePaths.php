<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ModulePaths
{
    public function __construct(private readonly Application $app)
    {
    }

    public function modulesPath(): string
    {
        return $this->absolutePath((string) config('ddd.modules_path', 'app/Modules'));
    }

    public function sharedPath(): string
    {
        return $this->absolutePath((string) config('ddd.shared_path', 'app/Shared'));
    }

    public function modulePath(string $module): string
    {
        return $this->modulesPath() . DIRECTORY_SEPARATOR . $this->moduleName($module);
    }

    public function moduleNamespace(string $module): string
    {
        return $this->namespaceFromPath((string) config('ddd.modules_path', 'app/Modules')) . '\\' . $this->moduleName($module);
    }

    public function moduleName(string $module): string
    {
        $module = trim($module);

        if ($module === '' || preg_match('/[.\/\\\\]/', $module) === 1) {
            throw new InvalidArgumentException("Invalid module name [{$module}].");
        }

        return Str::studly($module);
    }

    public function namespaceFromPath(string $path): string
    {
        $path = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $segments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $path)));

        if (($segments[0] ?? null) === 'app') {
            array_shift($segments);

            return rtrim($this->applicationNamespace(), '\\') . $this->namespaceSuffix($segments);
        }

        return ltrim($this->namespaceSuffix($segments), '\\');
    }

    public function inferModuleFromCurrentWorkingDirectory(): ?string
    {
        $cwd = getcwd();

        if ($cwd === false) {
            return null;
        }

        $modulesPath = realpath($this->modulesPath());

        if ($modulesPath === false) {
            return null;
        }

        $cwd = realpath($cwd) ?: $cwd;

        if (! str_starts_with($cwd, $modulesPath . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $relative = trim(substr($cwd, strlen($modulesPath)), DIRECTORY_SEPARATOR);
        $module = explode(DIRECTORY_SEPARATOR, $relative)[0] ?? null;

        return $module ?: null;
    }

    public function absolutePath(string $path): string
    {
        if ($path === '') {
            return $this->app->basePath();
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return $this->app->basePath($path);
    }

    public function applicationPath(string $path = ''): string
    {
        if (method_exists($this->app, 'path')) {
            return $this->app->path($path);
        }

        $basePath = $this->app->basePath('app');

        return $path !== ''
            ? $basePath . DIRECTORY_SEPARATOR . $path
            : $basePath;
    }

    public function applicationNamespace(): string
    {
        try {
            return $this->app->getNamespace();
        } catch (\Throwable) {
            return 'App\\';
        }
    }

    /**
     * @param array<int, string> $segments
     */
    private function namespaceSuffix(array $segments): string
    {
        if ($segments === []) {
            return '';
        }

        return '\\' . implode('\\', array_map(
            static fn (string $segment): string => Str::studly($segment),
            $segments,
        ));
    }
}
