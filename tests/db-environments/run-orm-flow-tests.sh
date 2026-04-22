#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

tests/db-environments/run-orm-flow-test.sh
tests/db-environments/run-orm-flow-test-mysql.sh
