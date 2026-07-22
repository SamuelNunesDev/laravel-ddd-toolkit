<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;

class StubRenderer
{
    public function __construct(
        private readonly Application $app,
        private readonly Filesystem $files,
    ) {
    }

    /**
     * @param array<string, mixed> $replacements
     */
    public function render(string $stubName, array $replacements): string
    {
        $contents = $this->files->get($this->stubPath($stubName));

        foreach ($replacements as $key => $value) {
            if (! is_string($value)) {
                throw new InvalidArgumentException(
                    "Replacement [{$key}] must be a string, " . get_debug_type($value) . ' given.',
                );
            }

            $contents = str_replace('{{ ' . $key . ' }}', $value, $contents);
            $contents = str_replace('{{' . $key . '}}', $value, $contents);
        }

        return $contents;
    }

    public function stubPath(string $stubName): string
    {
        $customPath = $this->app->basePath('stubs/vendor/laravel-ddd-toolkit/' . $stubName);

        if ($this->files->exists($customPath)) {
            return $customPath;
        }

        $packagePath = __DIR__ . '/../../stubs/' . $stubName;

        if ($this->files->exists($packagePath)) {
            return $packagePath;
        }

        throw new RuntimeException(
            "Stub [{$stubName}] was not found. Searched paths: {$customPath}, {$packagePath}.",
        );
    }
}
