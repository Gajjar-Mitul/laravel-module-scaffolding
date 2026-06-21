# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Unified blueprint configuration structure in `config/scaffolding.php`.
- Template-driven class/path/namespace resolution for generated artifacts.
- Optional artifact generators for factory, event, job, and module command.
- Queue strategy support (`job_only`, `infrastructure`, `both`) with queue infrastructure migration stubs.
- Queue infrastructure table-name customization for jobs/failed-jobs migrations.
- Queue action-based job generation with configurable class/path/namespace templates.
- Route template support for route name, URI segment, and route parameter naming.
- Feature tests covering custom naming templates, optional artifacts, queue strategy behavior, and route template behavior.
- Feature tests for custom queue table names and action-based queue job generation.

### Changed

- Core generators now resolve naming and file locations from artifact templates instead of fixed suffix/path conventions.
- README configuration section now documents the unified blueprint model and office-style conventions.
- README and troubleshooting docs now use unified config keys (`module.*`, `routing.*`, `artifacts.*`) and queue customization guidance.

## [0.1.0] - 2026-03-21

### Added

- End-to-end CRUD scaffolding command (`make:module`).
- Generation support for migration, model, enums, queries, services, controller, requests/DTOs, views, JS, and routes.
- YAML, schema, and interactive field resolution strategy.
- CI workflow for push and pull request test runs.
- Package test suite with Testbench coverage for generator output.

### Changed

- Routes are now generated as explicit manual route definitions (no `Route::resource`).
- Controllers now call query defaults directly (`getAll()`) without passing explicit column lists.
- Layout fallback detection now prefers real existing layouts (`layouts.app`, `app`, `layouts.master`, `master`).
- Enum validation in FormRequest now uses enum classes via `Rule::enum(...)`.
- Laravel compatibility constraints expanded to include Laravel 13.

### Notes

- Test harness upgraded to resolve PHP 8.5 deprecation warnings from earlier Testbench versions.
