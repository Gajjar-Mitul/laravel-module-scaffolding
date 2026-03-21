# Changelog

All notable changes to this project will be documented in this file.

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
