# DBC-003: Schema Inspector Contract

## 1) Title and ID
- ID: DBC-003
- Title: Schema Inspector Contract

## 2) Priority
- Priority: P0

## 3) Epic
- Epic: db-driver-contract

## 4) Type
- Type: contract

## 5) Size target
- Estimate: 2d

## 6) Goal
Define schema inspection invariants for table and column metadata.

## 7) Scope
- Define tableExists, columnExists, columnDefinition, columns contract semantics
- Define canonical metadata field names consumed by ORM

## 8) Non-goals
- No concrete PostgreSQL/MySQL SQL

## 9) Hard dependencies
- DBC-001

## 10) Soft dependencies (parallel-friendly)
- DBC-005

## 11) Files/areas impacted
- SchemaInspectorInterface
- ORM metadata adapters

## 12) Acceptance criteria (testable)
- Deliverable is implemented/documented exactly for this task scope.
- Hard dependencies are satisfied and referenced in implementation notes.
- No behavior regression outside touched scope.

## 13) Test requirements
- Add contract fixture tests for canonical metadata shape
- Validate ORM mapping assumptions against contract fields

## 14) Rollback/compat notes
- Keep function-first and modular usage paths intact.
- If partial rollout is needed, gate new behavior behind non-breaking defaults.
