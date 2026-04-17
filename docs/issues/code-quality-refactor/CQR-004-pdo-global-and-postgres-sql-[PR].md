# CQR-004 — yeapf-pdo-connection.php: global variables and hardcoded PostgreSQL SQL

## File
`src/database/yeapf-pdo-connection.php`

## Problems

### 1. global $yAnalyzer inside connect()
`connect()` uses `global $yAnalyzer` to interpolate the connection string. This violates module isolation rules. The analyzer should be injected or the interpolation replaced with a purpose-built connection string builder.

### 2. Hardcoded PostgreSQL SQL in shared class (AGENTS.md violation)
`tableExists()`, `columnDefinition()`, `columnExists()`, and `columns()` all embed PostgreSQL-specific `information_schema` SQL directly in `PDOConnection`. This is an explicit violation of the Database Architecture Rules in AGENTS.md:
> "Shared DB paths must not contain hardcoded engine SQL outside driver implementations."

### 3. Global functions CreateMainPDOConnection() / GetMainPDOConnection()
Two free functions at the bottom use `global $yeapfMainPDOConnection`. These should become static methods on an appropriate class or be removed in favour of the pool mechanism.

## What to do
- Remove `global $yAnalyzer` from `connect()` — build connection string without it or inject the dependency
- Move `tableExists`, `columnDefinition`, `columnExists`, `columns` into the `PostgreSQLDriver` implementation (tracked in `driver-postgresql` epic)
- Replace global functions with a static registry or pool accessor method

## Cross-reference
`driver-postgresql` epic — DPG-002 (schema introspection) is the destination for the SQL methods
