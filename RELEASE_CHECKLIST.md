# Release Checklist

Use this checklist before tagging `v1.0.0`.

## Scope Freeze

- [ ] Confirm v1 feature scope is frozen.
- [ ] Move all non-v1 ideas to backlog/issues.

## Quality Gates

- [ ] `composer validate --strict` passes.
- [ ] `composer test` passes.
- [ ] `vendor/bin/phpunit --display-deprecations` reports zero deprecations.
- [ ] CI workflow passes on all configured PHP versions.

## Real-World Validation

- [ ] Install package in a fresh Laravel app using local path repository.
- [ ] Install package in a second app (existing project-style structure).
- [ ] Publish config and stubs successfully.
- [ ] Generate one module in FormRequest mode.
- [ ] Generate one module in Spatie mode.
- [ ] Validate routes, controller, queries, services, requests/DTOs, views.

## Documentation

- [ ] README updated with latest behavior.
- [ ] `CHANGELOG.md` includes release notes.
- [ ] Upgrade notes are accurate.

## Packaging

- [ ] `composer.json` metadata is correct (name, description, keywords, constraints).
- [ ] No debug/temporary files included.

## Tag and Publish (when ready)

- [ ] Create git tag: `v1.0.0`.
- [ ] Push branch and tag.
- [ ] Publish/sync on Packagist.
- [ ] Verify install from clean external project.
