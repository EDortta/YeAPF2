# CQR-010 — class.key-data.php: checkConstraint() and __set() debug flags

## File
`src/classes/class.key-data.php`

## Irony
This file is the positive benchmark for code quality in `code-quality-agent.md`, yet `checkConstraint()` (~300 lines) and `__set()` both have `$debug = true` hardcoded, producing trace output in production.

## Problems

### 1. $debug = true in checkConstraint() (line 573)
Fires dozens of `_trace()` calls on every constraint check in production. This is the hottest path in the ORM.

### 2. $debug = true in __set() (line 964)
Fires on every property assignment.

### 3. checkConstraint() is ~300 lines
Handles validation for 8 types (string, int, float, bool, date, datetime, time, json) in a single method with an elseif chain. Each type branch should be a private method.

## What to do
- Set both `$debug` flags to `false` immediately as a stop-gap
- Remove `$debug` and all `_trace()` / print_r calls entirely
- Extract each type validation into a named private method:
  - `validateString(string $keyName, mixed $value, array $constraint): mixed`
  - `validateInt(string $keyName, mixed $value, array $constraint): mixed`
  - `validateFloat(...)`, `validateBool(...)`, `validateDate(...)`, `validateDatetime(...)`, `validateTime(...)`, `validateJson(...)`
- `checkConstraint()` becomes a dispatcher: look up type, call the appropriate validator

## Priority
HIGH — `$debug = true` on the hottest ORM path produces noise on every DB operation
