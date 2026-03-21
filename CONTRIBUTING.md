# Contributing

Thank you for contributing to Laravel Module Scaffolding.

This project focuses on configurable Laravel module scaffolding with clean generated output, predictable conventions, and minimal project-specific assumptions.

## Before You Start

- Check existing issues and discussions before opening a new one.
- For bugs, include a minimal reproduction.
- For feature requests, explain whether the change should be default behavior or opt-in via configuration.

## Development Requirements

- PHP 8.2+
- Composer 2+
- Git

## Local Setup

```bash
composer install
composer test
```

Optional local checks:

```bash
composer validate --strict
vendor/bin/phpunit --display-deprecations
find src tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Development Workflow

1. Create a focused branch.
2. Make the smallest reasonable change.
3. Add or update tests when behavior changes.
4. Update documentation when public behavior changes.
5. Run the local validation commands before opening a pull request.

## Pull Request Guidelines

- Keep pull requests narrowly scoped.
- Explain why the change is needed.
- Describe behavioral impact.
- Mention configuration or generated-output changes explicitly.
- Include before/after examples for scaffold output when relevant.

## Coding Guidelines

- Preserve existing package conventions unless the change intentionally evolves them.
- Prefer configuration or stub customization over hardcoding project-specific assumptions.
- Avoid breaking generated public behavior without documenting it.
- Keep generated output readable and production-usable.
- Do not introduce role/permission-specific assumptions into the generic scaffolding flow.

## Testing Expectations

Changes should generally include one or more of:

- unit/feature tests in the package test suite
- updated assertions for generated output
- manual validation notes when the change depends on external Laravel app context

## Reporting Bugs

When filing a bug, include:

- package version
- Laravel version
- PHP version
- command used
- relevant `config/scaffolding.php` overrides
- YAML definition used, if any
- generated file excerpts or stack trace

## Security

Please do not report security issues in public issues. See [SECURITY.md](SECURITY.md).
