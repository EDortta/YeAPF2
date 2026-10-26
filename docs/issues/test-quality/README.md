# Epic: test-quality

## Goal

Make Y2 tests repeatable and separate deterministic local tests from opt-in integration infrastructure.

## Motivation

The previous default suite mixed unit behavior with database sockets, duplicated discovery, used stale operational assumptions, and had no static/style/coverage/CI gates.

## Scope

Infrastructure contract, PHPUnit matrix, core/security/service behavioral tests, HTTP/2 environment, dual-engine driver parity, quality gates, coverage, and CI.

## Out of scope

Consumer applications, production data, deployment, external credentials, destructive Docker operations, and mass formatting of legacy files.

## Owning agent

`quality-gates-agent`, with DB/HTTP capability playbooks for their domains.

## Execution note

The operator explicitly requested implementation on `development`; therefore this epic was applied directly there rather than creating eight transient issue branches. All runtime integration gates were executed against the isolated `y2-test` project and passed.

## Issues

- [TQ-001 — infrastructure contract](TQ-001-infrastructure-contract-[closed].md)
- [TQ-002 — PHPUnit matrix](TQ-002-phpunit-matrix-[closed].md)
- [TQ-003 — core behavior](TQ-003-core-behavior-[closed].md)
- [TQ-004 — security and observability](TQ-004-security-observability-[closed].md)
- [TQ-005 — HTTP2 environment](TQ-005-http2-environment-[closed].md)
- [TQ-006 — service behavior](TQ-006-service-behavior-[closed].md)
- [TQ-007 — driver parity](TQ-007-driver-parity-[closed].md)
- [TQ-008 — quality gates](TQ-008-quality-gates-[closed].md)
