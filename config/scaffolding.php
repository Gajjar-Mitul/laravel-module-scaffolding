<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain Namespace
    |--------------------------------------------------------------------------
    | Root namespace for all generated domain classes.
    | e.g. "App\Domains" will produce App\Domains\Post\Controllers\PostController
    */
    'namespace' => 'App\\Domains',

    /*
    |--------------------------------------------------------------------------
    | Base Controller
    |--------------------------------------------------------------------------
    | The fully-qualified base controller all generated controllers extend.
    */
    'base_controller' => 'App\\Http\\Controllers\\Controller',

    /*
    |--------------------------------------------------------------------------
    | Validation Driver
    |--------------------------------------------------------------------------
    | Determines how request validation is handled.
    | Options: 'formrequest' | 'spatie'
    |
    | 'formrequest' → generates StoreXxxRequest / UpdateXxxRequest files
    | 'spatie'      → generates StoreXxxData / UpdateXxxData Spatie Data objects
    */
    'validation' => 'formrequest',

    /*
    |--------------------------------------------------------------------------
    | CSS Framework
    |--------------------------------------------------------------------------
    | Determines which CSS framework stubs to use for generated views.
    | Options: 'bootstrap' | 'tailwind'
    */
    'css_framework' => 'bootstrap',

    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    | Controls DataTable usage in the index view.
    | 'auto' → detect whether yajra/laravel-datatables is in composer.json
    | true   → always generate DataTable index
    | false  → always generate a plain table index
    */
    'datatable' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    | The Blade layout to @extend in generated views.
    | 'auto' → scan existing views and pick the most-used @extends value
    | or specify directly e.g. 'layouts.app'
    */
    'layout' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'file'       => 'routes/web.php',
        'prefix'     => '',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Options
    |--------------------------------------------------------------------------
    */
    'database' => [
        'audit_columns' => true,   // Add created_by / updated_by foreign keys
        'soft_deletes'  => false,  // Add deleted_at
        'timestamps'    => true,   // Add created_at / updated_at
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Guardrails
    |--------------------------------------------------------------------------
    | Enforces strict query-layer conventions in generated Queries classes.
    */
    'query' => [
        'enforce_explicit_select' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'views' => 'resources/views/modules',  // Base folder for generated views
        'js'    => 'resources/js/project',     // Base folder for generated JS files
        'yaml'  => 'scaffolding',              // Folder where *.yml field definitions live
        'trait' => 'app/Shared/Traits',        // Where to put TracksUserStamps trait
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
