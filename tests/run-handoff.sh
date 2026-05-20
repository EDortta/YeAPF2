#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# YeAPF2 (Y2) — test handoff runner
#
# Runs PHPUnit and writes machine-readable artifacts for a programmer agent.
# Does NOT fix code. See ERP-DryWall docs/agents/test-runner-handoff.md
#
# Usage:
#   ./tests/run-handoff.sh
#   ./tests/run-handoff.sh schema-bootstrap    # subset only
#   Y2_TEST_RESULTS_DIR=/tmp/y2-out ./tests/run-handoff.sh
# ---------------------------------------------------------------------------
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TESTS_DIR="$ROOT/tests"
OUT="${Y2_TEST_RESULTS_DIR:-$ROOT/.test-results/latest}"
SUITE="${1:-yeapf2}"

mkdir -p "$OUT"
TIMESTAMP="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

{
  echo "repo=Y2"
  echo "suite=$SUITE"
  echo "started_at=$TIMESTAMP"
  echo "php_version=$(php -r 'echo PHP_VERSION;')"
  echo "command=./tests/run-handoff.sh $SUITE"
} >"$OUT/meta.txt"

if [[ ! -x "$TESTS_DIR/vendor/bin/phpunit" ]]; then
  echo "ERROR: run 'composer install' in $TESTS_DIR first" | tee "$OUT/console.txt"
  echo 127 >"$OUT/exit-code.txt"
  exit 127
fi

echo "Starting PostgreSQL (tests/db-environments)..." | tee -a "$OUT/console.txt"
(
  cd "$TESTS_DIR/db-environments"
  docker compose up -d postgres 2>&1
) | tee -a "$OUT/console.txt"

cd "$TESTS_DIR"

PHPUNIT_ARGS=(
  -c phpunit.xml
  "--testsuite" "$SUITE"
  "--log-junit" "$OUT/junit.xml"
  "--testdox-text" "$OUT/testdox.txt"
)

set +e
php -d opcache.enable_cli=0 ./vendor/bin/phpunit "${PHPUNIT_ARGS[@]}" 2>&1 | tee -a "$OUT/console.txt"
EXIT=$?
set -e

echo "$EXIT" >"$OUT/exit-code.txt"
echo "finished_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)" >>"$OUT/meta.txt"
echo "exit_code=$EXIT" >>"$OUT/meta.txt"

# Human-readable one-liner for the fixer agent
if [[ "$EXIT" -eq 0 ]]; then
  echo "PASS — suite=$SUITE — artifacts in $OUT" >"$OUT/summary.txt"
else
  echo "FAIL — suite=$SUITE — read $OUT/junit.xml and $OUT/console.txt" >"$OUT/summary.txt"
fi

exit "$EXIT"
