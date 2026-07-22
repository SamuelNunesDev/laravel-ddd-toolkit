<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

class ModuleStructure
{
    /**
     * @return array<int, string>
     */
    public function directories(?string $preset = null): array
    {
        $preset = $preset ?: (string) config('ddd.default_preset', config('ddd.preset', 'hexagonal'));
        $directories = config("ddd.presets.{$preset}.directories");

        if (is_array($directories) && $directories !== []) {
            return array_values(array_map('strval', $directories));
        }

        return $this->fallbackHexagonalDirectories();
    }

    /**
     * @return array<int, string>
     */
    private function fallbackHexagonalDirectories(): array
    {
        return [
            'Domain/Entities',
            'Domain/ValueObjects',
            'Domain/Events',
            'Domain/Exceptions',
            'Application/UseCases',
            'Application/DTO',
            'Application/Ports/In',
            'Application/Ports/Out',
            'Infrastructure/Http/Controllers',
            'Infrastructure/Http/Requests',
            'Infrastructure/Persistence/Models',
            'Infrastructure/Persistence/Adapters',
            'Infrastructure/Integrations',
            'Infrastructure/Providers',
        ];
    }
}
