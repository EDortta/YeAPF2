# Testing Policy

## Purpose
Keep tests deterministic, readable, and safe for incremental refactors.

## What must be versioned
- Unit/integration tests under `tests/`
- Small deterministic fixtures needed by those tests
- Test helpers and contract harnesses

## What must not be versioned
- Credentials and secrets
- Machine-local editor/workspace files
- Caches and generated artifacts (unless explicitly part of test fixtures)

## Writing standards
- Use `declare(strict_types=1);` in PHP test files.
- Prefer `final` test classes unless extension is intentional.
- Use explicit return types (`: void`) for test methods.
- Prefer `assertSame()` when strict equality is expected.
- Keep one behavioral intent per test method.
- Avoid commented-out assertions and dead test code.

## Regression gate
- Any change touching critical hot-path files must keep `tests/CodeQualityRegressionTest.php` passing.

## Plugin tests
- For plugin role work, tests should cover:
  - registry wiring (`Registry::getDocumentValidator()`, type provider freeze behavior)
  - known-valid and known-invalid inputs
  - graceful behavior when validators are absent (where applicable)
