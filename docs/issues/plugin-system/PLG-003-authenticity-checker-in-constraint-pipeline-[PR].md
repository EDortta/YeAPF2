# PLG-003 — Wire authenticityChecker into SanitizedKeyData constraint pipeline

## File to modify
`src/classes/class.key-data.php`

## Problem
`SanitizedKeyData::checkConstraint()` applies type validation and regex but has no hook for algorithmic authenticity checks (e.g. CNPJ digit verification). The only way to add one today is subclassing or hacking the method.

## What to do

### 1. Add `authenticityChecker` to the constraint schema

In `setConstraint()` — add optional parameter:
```php
string|null $authenticityChecker = null
```

Store it in the constraint array:
```php
'authenticityChecker' => $authenticityChecker,
```

In `importConstraints()` — read it:
```php
'authenticityChecker' => $constraint['authenticityChecker'] ?? null,
```

### 2. Call the registry after the regex check in `checkConstraint()`

After the existing regex check block (line ~622), add:
```php
$checkerKey = $constraint['authenticityChecker'] ?? null;
if ($checkerKey !== null && $value !== null) {
    $validator = \YeAPF\Plugins\Registry::getDocumentValidator($checkerKey);
    if ($validator !== null && !$validator->validate($checkerKey, (string) $value)) {
        throw new \YeAPF\YeAPFException(
            "Value fails authenticity check '$checkerKey' in " . __CLASS__ . ' -> ' . $keyName,
            YeAPF_AUTHENTICITY_CHECK_FAILED
        );
    }
}
```

Note: if no validator is registered for the key, the check is silently skipped (graceful degradation — the plugin may not be loaded in all environments).

### 3. `BasicTypes` constraint arrays
No changes to `yTypes.php` for CNPJ/CPF — those definitions will be **replaced at boot** by `BrazilDocumentPlugin` via `TypeProviderInterface::getTypeDefinitions()` (PLG-004). The `authenticityChecker` field travels inside those definitions.

## Acceptance criteria
- Assigning a valid CNPJ (correct digit) to a constrained field: passes
- Assigning a structurally correct but algorithmically invalid CNPJ: throws `YeAPF_AUTHENTICITY_CHECK_FAILED`
- When no validator is registered for the key: assignment passes (no exception)
- `$debug = true` must NOT be introduced or left in this method (quality gate)
- Regression test added in `tests/SanitizedKeyDataTest.php` or a new `tests/AuthenticityCheckerTest.php`

## Notes
- This change touches `src/classes/class.key-data.php`, which is in the `CodeQualityRegressionTest.php` gate. The regression test must pass before merge.
- `YeAPF_AUTHENTICITY_CHECK_FAILED` constant is defined in PLG-001 work.
