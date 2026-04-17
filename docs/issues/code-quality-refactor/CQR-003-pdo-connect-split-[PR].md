# CQR-003 — yeapf-pdo-connection.php: split connect() method

## File
`src/database/yeapf-pdo-connection.php`

## Problem
`connect()` does two completely different things depending on the `$trulyConnected` flag:
- If `true`: opens a single real PDO connection with retry loop
- If `false`: builds the connection pool by instantiating multiple `self(true)` instances

This is a single-responsibility violation. The flag-based branching makes the constructor call flow (`__construct` → `connect`) hard to follow.

## What to do
- Extract `connectSingle(): void` — the retry-loop real connection logic
- Extract `buildPool(): void` — the pool initialization loop
- `connect()` becomes a one-line dispatcher, or is removed and callers are made explicit
- Remove the two commented-out lock blocks (lines 118-124, 141-146) — either implement or delete
