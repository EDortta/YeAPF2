# DBC-002: DBDriverInterface Methods

## 1) Title and ID
- ID: DBC-002
- Title: DBDriverInterface Methods

## 2) Priority
- Priority: P0

## 3) Epic
- Epic: db-driver-contract

## 4) Type
- Type: contract

## 5) Size target
- Estimate: 2d

## 6) Goal
Finalize generic DB driver method signatures for execution, capabilities, and normalized errors.

## 7) Scope
- Define query execution contract
- Define capability discovery methods
- Define error normalization contract return shape

## 8) Non-goals
- No engine SQL logic
- No schema diff logic

## 9) Hard dependencies
- DBC-001

## 10) Soft dependencies (parallel-friendly)
- DBC-004, DBC-008

## 11) Files/areas impacted
- DBDriverInterface, DriverCapabilities

## 12) Acceptance criteria (testable)
- Deliverable is implemented/documented exactly for this task scope.
- Hard dependencies are satisfied and referenced in implementation notes.
- No behavior regression outside touched scope.

## 13) Test requirements
- Add contract-level unit tests for expected method signatures
- Validate PHPStan interface contract checks

## 14) Rollback/compat notes
- Keep function-first and modular usage paths intact.
- If partial rollout is needed, gate new behavior behind non-breaking defaults.
