# DBC-007: Contract Test Harness

## 1) Title and ID
- ID: DBC-007
- Title: Contract Test Harness

## 2) Priority
- Priority: P0

## 3) Epic
- Epic: db-driver-contract

## 4) Type
- Type: test

## 5) Size target
- Estimate: 2d

## 6) Goal
Create reusable engine-agnostic test suite for all DB drivers.

## 7) Scope
- Add shared contract tests for execute/inspect/ddl semantics
- Define per-engine test bootstrap contract

## 8) Non-goals
- No engine-specific assertions outside opt-in sections

## 9) Hard dependencies
- DBC-002
- DBC-003
- DBC-005

## 10) Soft dependencies (parallel-friendly)
- DPG-007, DMY-007

## 11) Files/areas impacted
- tests/ contract suite
- test bootstrap config

## 12) Acceptance criteria (testable)
- Deliverable is implemented/documented exactly for this task scope.
- Hard dependencies are satisfied and referenced in implementation notes.
- No behavior regression outside touched scope.

## 13) Test requirements
- Run shared contract suite against at least one mock/stub driver

## 14) Rollback/compat notes
- Keep function-first and modular usage paths intact.
- If partial rollout is needed, gate new behavior behind non-breaking defaults.
