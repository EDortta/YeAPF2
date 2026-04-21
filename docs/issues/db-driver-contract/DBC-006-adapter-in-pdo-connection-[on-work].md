# DBC-006: Adapter in PDO Connection

## 1) Title and ID
- ID: DBC-006
- Title: Adapter in PDO Connection

## 2) Priority
- Priority: P0

## 3) Epic
- Epic: db-driver-contract

## 4) Type
- Type: refactor

## 5) Size target
- Estimate: 3d

## 6) Goal
Refactor PDO shared layer to delegate engine behavior to driver interfaces.

## 7) Scope
- Remove embedded engine-specific schema logic from yeapf-pdo-connection.php
- Inject/use driver contract in shared connection flow

## 8) Non-goals
- No new engine feature
- No MySQL implementation

## 9) Hard dependencies
- DBC-002
- DBC-003

## 10) Soft dependencies (parallel-friendly)
- DPG-006, DMY-006

## 11) Files/areas impacted
- src/database/yeapf-pdo-connection.php
- Driver resolution wiring

## 12) Acceptance criteria (testable)
- Deliverable is implemented/documented exactly for this task scope.
- Hard dependencies are satisfied and referenced in implementation notes.
- No behavior regression outside touched scope.

## 13) Test requirements
- Add regression tests for existing PostgreSQL behavior through adapter path

## 14) Rollback/compat notes
- Keep function-first and modular usage paths intact.
- If partial rollout is needed, gate new behavior behind non-breaking defaults.
