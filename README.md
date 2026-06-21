# Laravel Module Scaffolding

Generates backend modules by enforcing a consistent, opinionated architecture — eliminating repetitive CRUD work.

---

## 🚧 The Problem

Building CRUD in real-world applications is not just generating controllers and views.

A typical module required:
- model, migration, factory
- controller with grouped routes
- DTO (Spatie Data) for validation
- service layer
- query layer (no DB logic in controllers/services)
- JSON resources for API responses
- enums where needed

Additionally:
- strict architectural discipline had to be followed
- repetitive setup required manual tweaks (fillable fields, structure alignment)
- AI-generated code was inconsistent and required multiple corrections

⏱ A single module typically took **2–3 hours**

---

## ⚙️ The Solution

A CLI tool that generates modules with enforced structure and conventions.

With this:
- complete module generation happens in **seconds**
- architecture is applied consistently across projects
- no manual setup or repetitive adjustments

---

## 🚀 What It Does

- Generates full module structure
- Enforces Controller → Service → Query separation
- Uses DTO-based validation (Spatie Data)
- Uses Resource-based API responses
- Supports enums where required
- Applies DDD-inspired structure

---

## 🔄 Supported Workflows

### 1. YAML-driven (Schema-first)
- Define fields in YAML
- Generates full module automatically

### 2. Database-driven
- Scans existing database
- Generates module based on schema

---

## 🎯 Why This Matters

- Eliminates repetitive development overhead
- Enforces architectural consistency
- Reduces human error in structure and validation
- Makes systems easier to scale and maintain

> Built for real-world backend development, not generic CRUD generation.

## Design Goals

- Generic by default: no built-in role/permission assumptions
- Domain-oriented output structure
- Query-first data access pattern
- Service-based write operations
- Config-driven behavior with override-friendly stubs

## Features

- Domain-driven output under `App\\Domains\\{Module}` by default
- Generates:
  - Migration
  - Model
  - Enum classes
  - Query class
  - Service class
  - Controller
  - FormRequest or Spatie Data DTO validation classes
  - Blade views (Bootstrap or Tailwind stubs)
  - DataTable JS (optional/auto)
  - Route definitions
- Multiple field definition strategies:
  - Existing table schema
  - YAML definition file
  - Interactive CLI prompts
- Optional audit columns (`created_by`, `updated_by`)
- Optional soft deletes
- Customizable stub path

## Requirements

- PHP `^8.2`
- Laravel `10 | 11 | 12 | 13`

## Tested Compatibility Matrix

| Package | Supported |
|---|---|
| PHP | 8.4, 8.5 (CI tested) |
| Laravel | 10.x, 11.x, 12.x, 13.x |

The CI pipeline runs automated tests on PHP 8.4 and 8.5.

## Installation

### Install from Packagist

```bash
composer require gajjar-mitul/laravel-module-scaffolding
```

### Install from VCS (before Packagist)

In your target project `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Gajjar-Mitul/laravel-module-scaffolding.git"
    }
  ]
}
```

Then:

```bash
composer require gajjar-mitul/laravel-module-scaffolding:dev-main@dev
```

### Install via local path (development)

In your target project `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "/absolute/path/to/laravel-module-scaffolding",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Then:

```bash
composer require gajjar-mitul/laravel-module-scaffolding:dev-main
```

## Publish Assets

```bash
php artisan vendor:publish --tag=scaffolding-config
php artisan vendor:publish --tag=scaffolding-stubs
```

## Quick Start

### 1) Add YAML definition (optional)

Create `scaffolding/post.yml` in your Laravel app:

```yaml
fields:
  title:
    type: string
    nullable: false
    unique: false

  status:
    type: enum
    values: [draft, published, archived]
    nullable: false

  body:
    type: longText
    nullable: true

  user_id:
    type: foreignId
    related: User
    nullable: false
```

### 2) Generate module

```bash
php artisan make:module Post
```

Or with explicit overrides:

```bash
php artisan make:module Post --validation=spatie --css=tailwind --datatable
```

### 3) Run migration

```bash
php artisan migrate
```

## Command Options

```bash
php artisan make:module {name}
  --force
  --no-migration
  --no-views
  --no-js
  --no-routes
  --validation=formrequest|spatie
  --css=bootstrap|tailwind
  --datatable
  --no-datatable
```

## Generated Structure (default)

For `Post`:

```text
app/
  Domains/
    Post/
      Controllers/
        PostController.php
      Models/
        Post.php
      Queries/
        PostQueries.php
      Services/
        PostService.php
      Requests/               # if validation=formrequest
        StorePostRequest.php
        UpdatePostRequest.php
      DTOs/                   # if validation=spatie
        StorePostData.php
        UpdatePostData.php
      Enums/
        PostStatusEnum.php

resources/
  views/
    modules/
      post/
        index.blade.php
        create.blade.php
        edit.blade.php
        show.blade.php

resources/js/project/posts/index.js  # if DataTable mode is enabled
```

## Generated Layer Responsibilities

- Controller:
  - HTTP request/response orchestration
  - Delegates reads to Query class
  - Delegates writes to Service class
- Query class:
  - Read/query logic
  - Explicit select columns by default
- Service class:
  - Create/update/delete write workflows
  - Transaction wrapper for write operations
- Validation class:
  - FormRequest or Spatie Data DTO, based on config/flag

## Configuration

Published config: `config/scaffolding.php`

The package now uses one unified blueprint config.

Top-level sections:

- `module`: shared defaults (namespace, validation driver, css, datatable, layout, base paths)
- `routing`: route file/prefix/middleware plus route naming templates
- `database`: audit/soft delete behavior
- `query`: query guardrails
- `artifacts`: per-artifact enable/disable + class/path/namespace templates
- `stubs_path`: optional custom stubs root

### Template Tokens

Use these tokens in class/path/name templates:

- `{Module}`: Studly singular (Post)
- `{module}`: snake singular (post)
- `{Modules}`: Studly plural (Posts)
- `{modules}`: snake plural (posts)
- `{Class}`: resolved class name for the current artifact

### Unified Config Example (Office Style)

```php
return [
  'module' => [
    'namespace' => 'App\\Modules',
    'base_controller' => 'App\\Http\\Controllers\\BaseController',
    'validation' => 'formrequest',
    'css_framework' => 'tailwind',
    'datatable' => false,
    'layout' => 'layouts.admin',
    'paths' => [
      'views_base' => 'resources/views/backoffice/modules',
      'js_base' => 'resources/js/backoffice',
      'schema' => 'scaffolding',
      'traits' => 'app/Shared/Traits',
    ],
  ],

  'routing' => [
    'file' => 'routes/admin.php',
    'prefix' => 'admin',
    'middleware' => ['web', 'auth', 'verified'],
    'name_template' => 'admin_{modules}',
    'uri_template' => 'manage/{modules}',
    'parameter_template' => '{module}_item',
  ],

  'database' => [
    'audit_columns' => true,
    'soft_deletes' => true,
    'timestamps' => true,
  ],

  'query' => [
    'enforce_explicit_select' => true,
  ],

  'artifacts' => [
    'controller' => [
      'enabled' => true,
      'class_template' => '{Module}HttpController',
      'namespace_template' => '{namespace}\\{Module}\\Http',
      'path_template' => 'app/Modules/{Module}/Http/{Class}.php',
      'stub' => 'controller.stub',
    ],
    'query' => [
      'enabled' => true,
      'class_template' => '{Module}ReadModel',
      'namespace_template' => '{namespace}\\{Module}\\Read',
      'path_template' => 'app/Modules/{Module}/Read/{Class}.php',
      'stub' => 'queries.stub',
    ],
    'service' => [
      'enabled' => true,
      'class_template' => '{Module}Manager',
      'namespace_template' => '{namespace}\\{Module}\\Actions',
      'path_template' => 'app/Modules/{Module}/Actions/{Class}.php',
      'stub' => 'service.stub',
    ],
    'validation' => [
      'enabled' => true,
      'form_request' => [
        'store_class_template' => 'Create{Module}Request',
        'update_class_template' => 'Edit{Module}Request',
        'namespace_template' => '{namespace}\\{Module}\\Http\\Requests',
        'path_template' => 'app/Modules/{Module}/Http/Requests/{Class}.php',
        'stub' => 'form-request.stub',
      ],
    ],
    'factory' => [
      'enabled' => true,
      'class_template' => '{Module}Factory',
      'namespace_template' => 'Database\\Factories',
      'path_template' => 'database/factories/{Class}.php',
      'stub' => 'factory.stub',
    ],
    'event' => [
      'enabled' => true,
      'class_template' => '{Module}Created',
      'namespace_template' => 'App\\Events',
      'path_template' => 'app/Events/{Class}.php',
      'stub' => 'event.stub',
    ],
    'job' => [
      'enabled' => false,
      'class_template' => 'Process{Module}',
      'namespace_template' => 'App\\Jobs',
      'path_template' => 'app/Jobs/{Class}.php',
      'stub' => 'job.stub',
    ],
    'queue' => [
      'enabled' => true,
      'strategy' => 'both', // job_only|infrastructure|both
    ],
    'command' => [
      'enabled' => true,
      'class_template' => '{Module}SyncCommand',
      'namespace_template' => 'App\\Console\\Commands',
      'path_template' => 'app/Console/Commands/{Class}.php',
      'stub' => 'module-command.stub',
    ],
  ],

  'stubs_path' => null,
];
```

### Queue Strategy

- `job_only`: generate queue job artifact only
- `infrastructure`: generate queue table migrations only
- `both`: generate both queue job + queue table migrations

Invalid queue strategy values fail fast at command startup with a clear error.
Allowed values are exactly: `job_only`, `infrastructure`, `both`.

Queue infrastructure supports custom migration names and table names:

- `artifacts.queue.infrastructure.jobs_table_migration.name_template`
- `artifacts.queue.infrastructure.jobs_table_migration.table_name`
- `artifacts.queue.infrastructure.failed_jobs_table_migration.name_template`
- `artifacts.queue.infrastructure.failed_jobs_table_migration.table_name`

Queue job generation also supports action-based classes:

- `artifacts.queue.job.actions` (for example `['create', 'update', 'delete']`)
- `artifacts.queue.job.class_template` (for example `Process{Action}{Module}`)
- `artifacts.queue.job.namespace_template`
- `artifacts.queue.job.path_template`
- `artifacts.queue.job.stub`

### Route Template Behavior

- `routing.name_template`: route name base (for example `admin_{modules}` -> `admin_posts.index`)
- `routing.uri_template`: route URI segment (for example `manage/{modules}` -> `manage/posts`)
- `routing.parameter_template`: route/model parameter token (for example `{module}_item` -> `{post_item}`)

This lets you align route conventions with office standards without changing code.

## Data Source Resolution Priority

When you run `make:module`, fields are resolved in this order:

1. Existing DB table schema
2. YAML file in `module.paths.schema`
3. Interactive prompts

## Stub Customization

1. Publish stubs:

```bash
php artisan vendor:publish --tag=scaffolding-stubs
```

2. Set custom path in `config/scaffolding.php`:

```php
'stubs_path' => base_path('stubs/scaffolding'),
```

3. Modify only the stubs you need (others fall back to package defaults).

## Testing Matrix (Package)

Current tests cover:

- Command registration
- YAML-driven module generation
- Service layer injection in generated controller
- Query explicit-column generation
- No policy artifact generation (package stays generic)
- Spatie validation generation path
- Route generation idempotency
- Config behavior for wildcard select opt-out
- Custom artifact naming templates
- Optional artifact generation (factory, event, job, command)
- Queue strategy behavior (`both`) with infrastructure migrations
- Route templates (name, URI, parameter)
- Queue custom table names and action-based job classes

## Testing This Package

From the package root:

```bash
composer install
composer test
```

Optional:

```bash
php -l src/Commands/MakeModuleCommand.php
```

## Troubleshooting

- `make:module` asks interactive questions unexpectedly:
  - Ensure table does not already exist and YAML path/file name is correct.
- DataTable view generated when not expected:
  - Check `module.datatable` config and `--datatable/--no-datatable` flags.
- Custom stubs not picked up:
  - Confirm `stubs_path` exists and filenames match package stub names.
- Routes not appended:
  - Verify configured `routing.file` exists in target app.
- Queue jobs not generated for actions:
  - Ensure `artifacts.queue.enabled=true`, queue strategy is `job_only` or `both`, and `artifacts.queue.job.actions` is not empty.
- Queue migrations generated with wrong table names:
  - Check `artifacts.queue.infrastructure.*.table_name` values and migration stubs.

## Community

- Code of conduct: [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- Contribution guide: [CONTRIBUTING.md](CONTRIBUTING.md)
- Security policy: [SECURITY.md](SECURITY.md)
- Changelog: [CHANGELOG.md](CHANGELOG.md)
- Release gate: [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md)

## Release Checklist

1. Update changelog/release notes
2. Ensure tests pass
3. Tag a version:

```bash
git tag v0.1.0
git push origin v0.1.0
```

4. Publish on Packagist (or let webhook auto-sync)

For the full pre-v1 gate list, see [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md).

## Upgrade Notes

If upgrading from early drafts:

- Config is now unified under `module`, `routing`, and `artifacts` sections.
- Route generation now uses manual route definitions, not `Route::resource`.
- Controller index now calls query defaults (`getAll()`) rather than passing column arrays.
- Enum validation now uses `Rule::enum(YourEnum::class)` and generated enum classes.
- Layout auto-detection fallback is now `layouts.app` when no extends match is found.
- Queue generation now supports strategy-based output, action-specific job classes, and configurable queue table names.

See full details in [CHANGELOG.md](CHANGELOG.md).

## License

MIT
