# DBC-005: DDL Synthesizer Contract

## 1) Title and ID
- ID: DBC-005
- Title: DDL Synthesizer Contract

## 2) Priority
- Priority: P0

## 3) Epic
- Epic: db-driver-contract

## 4) Type
- Type: contract

## 5) Size target
- Estimate: 2d

## 6) Goal
Define DDL synthesis input/output contract for table/alter/enum/fk/index.

## 7) Scope
- Define canonical manifest-diff input model
- Define generated statements output model
- Define idempotency expectations

## 8) Non-goals
- No engine-specific SQL generation

## 9) Hard dependencies
- DBC-003

## 10) Soft dependencies (parallel-friendly)
- DSM-001

## 11) Files/areas impacted
- DDLSynthesizerInterface
- Schema diff DTOs

## 12) Acceptance criteria (testable)
- Deliverable is implemented/documented exactly for this task scope.
- Hard dependencies are satisfied and referenced in implementation notes.
- No behavior regression outside touched scope.

## 13) Test requirements
- Add contract tests for expected output shape by input scenario

## 14) Rollback/compat notes
- Keep function-first and modular usage paths intact.
- If partial rollout is needed, gate new behavior behind non-breaking defaults.
