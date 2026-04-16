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

Key options:

- `namespace`: root namespace for generated domain classes
- `base_controller`: base controller FQCN
- `validation`: `formrequest` or `spatie`
- `css_framework`: `bootstrap` or `tailwind`
- `datatable`: `auto`, `true`, or `false`
- `layout`: `auto` or explicit Blade layout
- `routes.file`, `routes.prefix`, `routes.middleware`
- `database.audit_columns`, `database.soft_deletes`, `database.timestamps`
- `query.enforce_explicit_select`
- `paths.views`, `paths.js`, `paths.yaml`, `paths.trait`
- `stubs_path`

Example overrides:

```php
return [
  'namespace' => 'App\\Domain',
  'validation' => 'spatie',
  'css_framework' => 'tailwind',
  'datatable' => false,
  'query' => [
    'enforce_explicit_select' => true,
  ],
  'routes' => [
    'file' => 'routes/web.php',
    'prefix' => 'admin',
    'middleware' => ['web', 'auth'],
  ],
];
```

## Data Source Resolution Priority

When you run `make:module`, fields are resolved in this order:

1. Existing DB table schema
2. YAML file in `paths.yaml`
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
  - Check `datatable` config and `--datatable/--no-datatable` flags.
- Custom stubs not picked up:
  - Confirm `stubs_path` exists and filenames match package stub names.
- Routes not appended:
  - Verify configured `routes.file` exists in target app.

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

- Route generation now uses manual route definitions, not `Route::resource`.
- Controller index now calls query defaults (`getAll()`) rather than passing column arrays.
- Enum validation now uses `Rule::enum(YourEnum::class)` and generated enum classes.
- Layout auto-detection fallback is now `layouts.app` when no extends match is found.

See full details in [CHANGELOG.md](CHANGELOG.md).

## License

MIT
