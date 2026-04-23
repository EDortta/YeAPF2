# PLG-005 — LatamDocumentPlugin: migrate document-checker.php (UY/AR/PE)

## Files to modify / create
- `src/plugins/latam-document-plugin.php` (new)
- `src/plugins/document-checker.php` (remove or deprecate)

## Problem
`document-checker.php` contains working algorithmic validators for Uruguay (CI), Argentina (DNI), and Peru (DNI/RUC) but:
- All `check_id_*` methods are `private` — third parties cannot extend without copying
- Registered as `global $customerDocumentChecker` — anti-pattern, collides with any other variable of that name
- Not connected to the constraint pipeline (PLG-003)
- No type definitions provided — no `sedInput`/`sedOutput`, no `authenticityChecker` key
- Class name `CustomerDocumentChecker` leaks a domain assumption into the framework

## What to do

Create `LatamDocumentPlugin` implementing `DocumentValidatorInterface` and `TypeProviderInterface`:

### Validator methods (extracted from `document-checker.php`)
- `validateUY(string $value): bool` — current `check_id_UY` logic, made package-private or `protected`
- `validateAR(string $value): bool` — current `check_id_AR` logic
- `validatePE(string $value): bool` — current `check_id_PE` logic

`getSupportedKeys()` returns `['UY.CI', 'AR.DNI', 'PE.DNI']`.

`validate(string $key, string $value): bool` dispatches by key.

### Type definitions (`getTypeDefinitions()`)
Provide minimal but correct constraint arrays for:
- `UY_CI` — 8 digits, strip non-digits on input, `authenticityChecker => 'UY.CI'`
- `AR_DNI` — 8 digits, strip non-digits on input, `authenticityChecker => 'AR.DNI'`
- `PE_DNI` — 8–9 chars (alphanumeric), `authenticityChecker => 'PE.DNI'`

(Type names use underscore to avoid collision with generic ISO codes; open for discussion.)

### Self-registration
```php
$plugin = new LatamDocumentPlugin();
\YeAPF\Plugins\Registry::registerDocumentValidator($plugin);
\YeAPF\Plugins\Registry::registerTypeProvider($plugin);
```

### Removal of `document-checker.php`
After `LatamDocumentPlugin` is complete and tests pass:
- Remove `src/plugins/document-checker.php`
- Remove `global $customerDocumentChecker` from any consumer that referenced it (grep first)

## Acceptance criteria
- All three existing validators produce the same pass/fail results as the original `document-checker.php` methods for the same inputs
- `global $customerDocumentChecker` no longer exists anywhere in the codebase
- Conformance test (`PLG-006` base class) passes for this plugin
- No behavior regression in any consumer that called `$customerDocumentChecker->validate(...)`

## Notes
- This issue depends on PLG-001 (interfaces) and PLG-002 (registry). Do not start until both are merged.
- The Peru validator uses a nested closure inside `check_id_PE` — extract to a named private method during migration.
