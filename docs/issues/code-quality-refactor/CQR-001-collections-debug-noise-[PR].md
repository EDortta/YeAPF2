# CQR-001 — yeapf-collections.php: remove debug noise

## File
`src/database/yeapf-collections.php`

## Problem
51 `_trace()` / `_log()` / `print_r()` calls in production code paths. Several are gated behind `$debug = true/false` local variables that are toggled inconsistently (some set to `true` mid-method at line 953, left hardcoded).

## What to do
- Remove all `_trace()` / `print_r()` calls that are not routed through the proper logger interface
- Replace any genuinely useful diagnostics with `yLogger::trace()` calls at appropriate levels
- Remove `$debug` local variables and their conditional blocks entirely
- Delete all commented-out debug code blocks

## Verification
- `grep -n "_trace\|print_r\|var_dump\|\$debug" src/database/yeapf-collections.php` should return zero results after the fix
