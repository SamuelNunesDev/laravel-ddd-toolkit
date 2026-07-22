<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

class ModuleStructure
{
    /**
     * @return array<int, string>
     */
    public function directories(): array
    {
        if (! (bool) config('ddd.default_domain_structure', true)) {
            return [
                'Domain',
                'Application',
                'Infrastructure',
            ];
        }

        $preset = (string) config('ddd.preset', 'default');
        $directories = config("ddd.presets.{$preset}.directories");

        if (is_array($directories) && $directories !== []) {
            return array_values(array_map('strval', $directories));
        }

        return [
            'Domain/Entities',
            'Domain/ValueObjects',
            'Domain/Events',
            'Domain/Exceptions',
            'Domain/Contracts',
            'Application/Commands',
            'Application/Queries',
            'Application/DTO',
            'Application/Handlers',
            'Infrastructure/Http/Controllers',
            'Infrastructure/Http/Requests',
            'Infrastructure/Persistence/Models',
            'Infrastructure/Persistence/Repositories',
            'Infrastructure/Integrations',
            'Infrastructure/Jobs',
            'Infrastructure/Listeners',
            'Infrastructure/Providers',
        ];
    }
}
