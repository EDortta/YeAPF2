# Epic: db-driver-contract

## Goal
Define and stabilize engine-agnostic driver contracts so YeAPF2 can support multiple database backends without engine-specific SQL leaking into shared code.

## Motivation
Currently, PostgreSQL-specific SQL is embedded in `yeapf-pdo-connection.php` and `yeapf-collections.php`. This blocks MySQL support and makes the shared layer brittle.

## Scope
- Define `DBDriverInterface`, `SchemaInspectorInterface`, `QueryDialectInterface`, `DriverCapabilities`, and `DDLSynthesizerInterface` under `YeAPF\Connection\DB\Driver\`
- Migrate shared SQL out of generic classes into engine drivers
- Provide a contract test suite that any driver must pass

## Out of Scope
- Concrete driver implementations (see `driver-postgresql` and `driver-mysql` epics)
- ORM/DocumentModel mapping (see `db-schema-manifest`)

## Agent
`db-driver-contract-agent`

## Issues
- [DBC-001-driver-contract-skeleton-[on-work].md](./DBC-001-driver-contract-skeleton-[on-work].md)
- [DBC-002-dbdriverinterface-methods-[on-work].md](./DBC-002-dbdriverinterface-methods-[on-work].md)
- [DBC-003-schema-inspector-contract-[on-work].md](./DBC-003-schema-inspector-contract-[on-work].md)
- [DBC-004-query-dialect-contract-[opened].md](./DBC-004-query-dialect-contract-[opened].md)
- [DBC-005-ddl-synthesizer-contract-[on-work].md](./DBC-005-ddl-synthesizer-contract-[on-work].md)
- [DBC-006-adapter-in-pdo-connection-[on-work].md](./DBC-006-adapter-in-pdo-connection-[on-work].md)
- [DBC-007-contract-test-harness-[opened].md](./DBC-007-contract-test-harness-[opened].md)
- [DBC-008-error-normalization-policy-[opened].md](./DBC-008-error-normalization-policy-[opened].md)
- [DBC-009-docs-and-migration-notes-[opened].md](./DBC-009-docs-and-migration-notes-[opened].md)
