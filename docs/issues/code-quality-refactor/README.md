# Epic: code-quality-refactor

## Goal
Bring the codebase up to the standard set by `src/classes/class.key-data.php`: short focused methods, no debug noise, no global variables, no commented-out code. Apply `code-quality-agent` rules across all files that fall short.

## Motivation
A full scan of the codebase (2026-04-17) identified systematic issues: hardcoded `$debug = true` flags, `_trace()` calls in production paths, `global` variables inside methods, methods over 100 lines, and `echo`/`var_dump`/`die()` left in non-debug code. This makes the code hard to read, maintain, and trust.

## Positive Benchmark
`src/classes/class.key-data.php` — short focused methods, PHPDoc on every public method, no debug noise, consistent indentation.

## Scope
One issue per file. Each issue targets the specific problems found in that file.

## Agent
`code-quality-agent` → `psr-compliance-agent` for style conformance after structural refactor → `quality-gates-agent` to verify tests pass

## Issues
- [CQR-001-collections-debug-noise.md](./CQR-001-collections-debug-noise-[closed].md)
- [CQR-002-collections-exportdocumentmodel.md](./CQR-002-collections-exportdocumentmodel-[PR].md)
- [CQR-003-pdo-connect-split.md](./CQR-003-pdo-connect-split-[closed].md)
- [CQR-004-pdo-global-and-postgres-sql.md](./CQR-004-pdo-global-and-postgres-sql-[closed].md)
- [CQR-005-webapp-globals-and-debug.md](./CQR-005-webapp-globals-and-debug-[closed].md)
- [CQR-006-sse-callbacks-and-echo.md](./CQR-006-sse-callbacks-and-echo-[closed].md)
- [CQR-007-i18n-translate-refactor.md](./CQR-007-i18n-translate-refactor-[closed].md)
- [CQR-008-jwt-secret-in-logs.md](./CQR-008-jwt-secret-in-logs-[closed].md)
- [CQR-009-yparser-get-method.md](./CQR-009-yparser-get-method-[closed].md)
- [CQR-010-keydata-checkconstraint.md](./CQR-010-keydata-checkconstraint-[closed].md)
