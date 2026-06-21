<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module Blueprint
    |--------------------------------------------------------------------------
    | Core module generation behavior and shared conventions.
    */
    'module' => [
        'namespace'       => 'App\\Domains',
        'base_controller' => 'App\\Http\\Controllers\\Controller',
        'validation'      => 'formrequest', // formrequest|spatie
        'css_framework'   => 'bootstrap',   // bootstrap|tailwind
        'datatable'       => 'auto',        // auto|true|false
        'layout'          => 'auto',        // auto|layouts.app|...

        'paths' => [
            'views_base' => 'resources/views/modules',
            'js_base'    => 'resources/js/project',
            'schema'     => 'scaffolding',
            'traits'     => 'app/Shared/Traits',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing Blueprint
    |--------------------------------------------------------------------------
    */
    'routing' => [
        'file'       => 'routes/web.php',
        'prefix'     => '',
        'middleware' => ['web', 'auth'],
        'name_template' => '{modules}',
        'uri_template' => '{modules}',
        'parameter_template' => '{module}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Blueprint
    |--------------------------------------------------------------------------
    */
    'database' => [
        'audit_columns' => true,
        'soft_deletes'  => false,
        'timestamps'    => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Blueprint
    |--------------------------------------------------------------------------
    */
    'query' => [
        'enforce_explicit_select' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Artifact Blueprint (breaking-change foundation)
    |--------------------------------------------------------------------------
    | Each artifact can be turned on/off and assigned naming/path templates.
    | Supported tokens: {Module}, {module}, {Modules}, {modules}
    |
    | Note: current generators still use legacy internals; these entries are the
    | source of truth for the incremental refactor.
    */
    'artifacts' => [
        'migration' => [
            'enabled'       => true,
            'class_template' => 'Create{Modules}Table',
            'path_template'  => 'database/migrations',
            'stub'           => 'migration.stub',
        ],
        'model' => [
            'enabled'         => true,
            'namespace_template' => '{namespace}\\{Module}\\Models',
            'class_template'  => '{Module}',
            'path_template'   => 'app/Domains/{Module}/Models/{Class}.php',
            'stub'            => 'model.stub',
        ],
        'enum' => [
            'enabled'       => true,
            'class_template' => '{Module}{Field}Enum',
            'path_template'  => 'app/Domains/{Module}/Enums/{Class}.php',
            'stub'           => 'enum.stub',
        ],
        'query' => [
            'enabled'       => true,
            'class_template' => '{Module}Queries',
            'path_template'  => 'app/Domains/{Module}/Queries/{Class}.php',
            'stub'           => 'queries.stub',
        ],
        'service' => [
            'enabled'       => true,
            'class_template' => '{Module}Service',
            'path_template'  => 'app/Domains/{Module}/Services/{Class}.php',
            'stub'           => 'service.stub',
        ],
        'controller' => [
            'enabled'       => true,
            'class_template' => '{Module}Controller',
            'path_template'  => 'app/Domains/{Module}/Controllers/{Class}.php',
            'stub'           => 'controller.stub',
        ],
        'validation' => [
            'enabled' => true,
            'form_request' => [
                'store_class_template'  => 'Store{Module}Request',
                'update_class_template' => 'Update{Module}Request',
                'path_template'         => 'app/Domains/{Module}/Requests/{Class}.php',
                'stub'                  => 'form-request.stub',
            ],
            'spatie_data' => [
                'store_class_template'  => 'Store{Module}Data',
                'update_class_template' => 'Update{Module}Data',
                'path_template'         => 'app/Domains/{Module}/DTOs/{Class}.php',
                'stub'                  => 'spatie-dto.stub',
            ],
        ],
        'views' => [
            'enabled'       => true,
            'path_template' => 'resources/views/modules/{module}',
        ],
        'javascript' => [
            'enabled'       => true,
            'path_template' => 'resources/js/project/{modules}/index.js',
        ],
        'routes' => [
            'enabled' => true,
        ],
        'factory' => [
            'enabled'       => false,
            'class_template' => '{Module}Factory',
            'namespace_template' => 'Database\\Factories',
            'path_template'  => 'database/factories/{Class}.php',
            'stub'           => 'factory.stub',
        ],
        'event' => [
            'enabled'       => false,
            'class_template' => '{Module}Created',
            'namespace_template' => 'App\\Events',
            'path_template'  => 'app/Events/{Class}.php',
            'stub'           => 'event.stub',
        ],
        'job' => [
            'enabled'       => false,
            'class_template' => 'Process{Module}',
            'namespace_template' => 'App\\Jobs',
            'path_template'  => 'app/Jobs/{Class}.php',
            'stub'           => 'job.stub',
        ],
        'queue' => [
            'enabled'  => false,
            'strategy' => 'job_only', // job_only|infrastructure|both
            'job' => [
                'actions' => [], // e.g. ['create', 'update', 'delete']
                'class_template' => 'Process{Action}{Module}',
                'namespace_template' => 'App\\Jobs',
                'path_template' => 'app/Jobs/{Class}.php',
                'stub' => 'job.stub',
            ],
            'infrastructure' => [
                'jobs_table_migration' => [
                    'enabled' => true,
                    'path_template' => 'database/migrations',
                    'stub' => 'queue/jobs-table.stub',
                    'name_template' => 'create_jobs_table',
                    'table_name' => 'jobs',
                ],
                'failed_jobs_table_migration' => [
                    'enabled' => true,
                    'path_template' => 'database/migrations',
                    'stub' => 'queue/failed-jobs-table.stub',
                    'name_template' => 'create_failed_jobs_table',
                    'table_name' => 'failed_jobs',
                ],
            ],
        ],
        'command' => [
            'enabled'       => false,
            'class_template' => '{Module}SyncCommand',
            'namespace_template' => 'App\\Console\\Commands',
            'path_template'  => 'app/Console/Commands/{Class}.php',
            'stub'           => 'module-command.stub',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Stubs Path
    |--------------------------------------------------------------------------
    | Set to a directory path to use your own stubs instead of the package
    | defaults. Run `php artisan vendor:publish --tag=scaffolding-stubs` first.
    */
    'stubs_path' => null,

];
