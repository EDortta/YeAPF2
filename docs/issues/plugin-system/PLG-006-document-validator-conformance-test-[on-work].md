# PLG-006 — Document Validator Conformance Test

## Files to create/update
- `tests/DocumentValidatorConformanceTestCase.php` (new abstract base)
- `tests/BrazilDocumentPluginTest.php`
- `tests/LatamDocumentPluginTest.php`

## Problem
Document validator plugin tests currently duplicate setup and core assertions. This creates uneven quality and increases maintenance cost as new country validators are added.

## What to do
Create a reusable abstract PHPUnit contract for document validator plugins and migrate existing plugin tests to it.

### Required conformance contract
- Plugin validator is registered for all declared validator keys.
- Declared type definitions exist and expose the expected `authenticityChecker`.
- Known valid fixtures return `true`.
- Known invalid fixtures return `false`.
- Registry bootstrapping/reset helper is shared and deterministic.

### Existing plugin migrations
- `BrazilDocumentPluginTest` must extend the new contract.
- `LatamDocumentPluginTest` must extend the new contract and keep parity checks needed for migrated legacy behavior.

## Acceptance criteria
- Brazil and LATAM tests run through the same abstract conformance flow.
- No duplicated registry reset/bootstrap code between those tests.
- PHPUnit passes for:
  - `tests/BrazilDocumentPluginTest.php`
  - `tests/LatamDocumentPluginTest.php`
  - `tests/AuthenticityCheckerTest.php`
- No production-path debug noise introduced.

## Notes
- Keep plugin-specific behavior checks in concrete tests; keep role contract checks in the abstract base.
