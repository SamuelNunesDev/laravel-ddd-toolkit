<?php

return [
    'default_preset' => 'hexagonal',

    'modules_path' => 'app/Modules',

    'shared_path' => 'app/Shared',

    'create_repositories' => false,

    'create_policies' => false,

    'create_jobs' => true,

    'create_events' => true,

    'agents' => [
        'enabled' => true,
        'filename' => 'AGENTS.md',
        'publish_on_install' => true,
    ],

    'presets' => [
        'hexagonal' => [
            'directories' => [
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
            ],
        ],

        'minimal' => [
            'directories' => [
                'Domain',
                'Application',
                'Infrastructure',
            ],
        ],

        'tactical' => [
            'directories' => [
                'Domain/Entities',
                'Domain/ValueObjects',
                'Domain/Events',
                'Domain/Exceptions',
                'Application/DTO',
                'Application/UseCases',
                'Infrastructure/Http',
                'Infrastructure/Persistence',
            ],
        ],

        'full' => [
            'directories' => [
                'Domain/Aggregates',
                'Domain/Entities',
                'Domain/ValueObjects',
                'Domain/Events',
                'Domain/Exceptions',
                'Application/DTO',
                'Application/UseCases',
                'Application/Ports/In',
                'Application/Ports/Out',
                'Infrastructure/Http/Controllers',
                'Infrastructure/Http/Requests',
                'Infrastructure/Persistence/Models',
                'Infrastructure/Persistence/Adapters',
                'Infrastructure/Persistence/Repositories',
                'Infrastructure/Integrations',
                'Infrastructure/Jobs',
                'Infrastructure/Listeners',
                'Infrastructure/Policies',
                'Infrastructure/Providers',
            ],
        ],
    ],
];
