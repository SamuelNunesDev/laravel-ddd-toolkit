<?php

return [
    'modules_path' => 'app/Modules',

    'shared_path' => 'app/Shared',

    'default_domain_structure' => true,

    'create_repositories' => false,

    'create_policies' => false,

    'create_jobs' => true,

    'create_events' => true,

    'preset' => 'default',

    'presets' => [
        'minimal' => [
            'directories' => [
                'Domain',
                'Application',
                'Infrastructure',
            ],
        ],

        'default' => [
            'directories' => [
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
            ],
        ],

        'full' => [
            'directories' => [
                'Domain/Aggregates',
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
                'Infrastructure/Policies',
                'Infrastructure/Providers',
            ],
        ],
    ],
];
