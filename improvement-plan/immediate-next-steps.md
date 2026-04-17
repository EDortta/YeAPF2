# Immediate Next Steps
# (original notes preserved below, expanded with tracked issues in priority order)

## Original notes
* Refactor and clean up code structure
* Improve HTTP2 service abstraction
* Implement better testing and debugging tools
* Automate as much as possible

---

## Prioritized issue list
Issues are drawn from docs/issues/. Priority order: security first, then production-path correctness, then architecture foundations, then feature work.

### CRITICAL — fix before any other work

1. **CQR-008** `yeapf-jwt.php` — JWT secret key written to trace log unconditionally
   → `docs/issues/code-quality-refactor/CQR-008-jwt-secret-in-logs-[closed].md`

2. **CQR-010** `class.key-data.php` — `$debug = true` hardcoded in `checkConstraint()` and `__set()`; fires on every ORM property set in production
   → `docs/issues/code-quality-refactor/CQR-010-keydata-checkconstraint-[closed].md`

### HIGH — production noise / architectural violations

3. **CQR-001** `yeapf-collections.php` — 51 debug trace calls in production paths
   → `docs/issues/code-quality-refactor/CQR-001-collections-debug-noise-[closed].md`

4. **CQR-004** `yeapf-pdo-connection.php` — PostgreSQL SQL hardcoded in shared class (AGENTS.md violation) + `global $yAnalyzer` inside method
   → `docs/issues/code-quality-refactor/CQR-004-pdo-global-and-postgres-sql-[closed].md`

5. **CQR-007** `i18n.php` — `$debug = true` hardcoded in `translate()`; fires in production
   → `docs/issues/code-quality-refactor/CQR-007-i18n-translate-refactor-[closed].md`

### MEDIUM — structural refactors (readability and maintainability)

6. **CQR-003** `yeapf-pdo-connection.php` — `connect()` does two unrelated things; commented-out lock blocks
   → `docs/issues/code-quality-refactor/CQR-003-pdo-connect-split-[closed].md`

7. **CQR-005** `yeapf-webapp.php` — `global $yAnalyzer` in two static methods; live `var_dump` + `die()` behind `$debug=false`; `setRouteHandler()` is 83 lines
   → `docs/issues/code-quality-refactor/CQR-005-webapp-globals-and-debug-[closed].md`

8. **CQR-006** `yeapf-sse-service.php` — `echo` instead of logger; `start()` 125 lines; nested coroutine inside callback
   → `docs/issues/code-quality-refactor/CQR-006-sse-callbacks-and-echo-[closed].md`

9. **H2S-001** `yeapf-http2-service.php` — `getAsOpenAPIJSON()` is 175 lines; split into per-section builders
   → `docs/issues/http2-service/H2S-001-refactor-get-as-openapi-json-[opened].md`

10. **H2S-002** `yeapf-http2-service.php` — `'Request'` callback: IIFE inside closure, `global $currentURI`, commented debug blocks
    → `docs/issues/http2-service/H2S-002-refactor-request-callback-[opened].md`

11. **CQR-009** `yParser.php` — `get()` is 199 lines; extract one reader method per token type
    → `docs/issues/code-quality-refactor/CQR-009-yparser-get-method-[closed].md`

12. **CQR-002** `yeapf-collections.php` — `exportDocumentModel()` handles JSON+SQL+Protobuf in one method
    → `docs/issues/code-quality-refactor/CQR-002-collections-exportdocumentmodel-[on-work].md`

### FOUNDATION — DB architecture (must precede driver and schema work)

13. **DBC-001..009** `db-driver-contract` epic — define `DBDriverInterface`, `SchemaInspectorInterface`, `QueryDialectInterface`, `DDLSynthesizerInterface`
    → `docs/issues/db-driver-contract/`

14. **DSM-001..006** `db-schema-manifest` epic — JSON as single schema source of truth; extend format for enums, FKs, indexes
    → `docs/issues/db-schema-manifest/`

### FEATURE — driver implementations (after contract is defined)

15. **DPG-001..008** `driver-postgresql` epic — clean PostgreSQL driver behind contract
    → `docs/issues/driver-postgresql/`

16. **DMY-001..008** `driver-mysql` epic — MySQL parity under same contract
    → `docs/issues/driver-mysql/`

### FEATURE — HTTP2 service improvements (after structural refactor)

17. **H2S-003** Middleware pipeline (auth, logging, rate limiting)
18. **H2S-004** OpenAPI auto-generation from service definitions
19. **H2S-005** Service definition simplification (WebApp-style ergonomics)

### TOOLING

20. **YeAPF2-tools** epic — `schema:check`, `schema:apply`, `app:create`, `app:deploy`
    → `docs/issues/yeapf2-tools/`

### ONGOING — cross-cutting, apply by boy-scout rule

21. **psr-compliance** epic — PSR-1/3/4/12, `declare(strict_types=1)`, PHPStan baseline
    → `docs/issues/psr-compliance/`
