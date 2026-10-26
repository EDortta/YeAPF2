#!/usr/bin/env bash
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TESTS_DIR="$ROOT/tests"
SUITE="${1:-default}"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUT="${Y2_TEST_RESULTS_DIR:-$ROOT/.test-results/$SUITE/$TIMESTAMP}"

case "$SUITE" in
    default|unit|contract)
        SERVICES=()
        ;;
    integration-postgres)
        SERVICES=(postgres)
        ;;
    integration-mysql)
        SERVICES=(mysql)
        ;;
    integration)
        SERVICES=(postgres mysql)
        ;;
    integration-http2)
        SERVICES=(php-http caddy-http)
        ;;
    *)
        echo "ERROR: unknown suite '$SUITE'. Expected default, unit, contract, integration-postgres, integration-mysql, integration, or integration-http2." >&2
        exit 64
        ;;
esac

mkdir -p "$OUT"
COMMAND="./tests/run-handoff.sh $SUITE"
{
    echo "repo=Y2"
    echo "suite=$SUITE"
    echo "started_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    echo "php_version=$(php -r 'echo PHP_VERSION;')"
    echo "command=$COMMAND"
} >"$OUT/meta.txt"

finish() {
    local exit_code="$1"
    echo "$exit_code" >"$OUT/exit-code.txt"
    {
        echo "finished_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        echo "exit_code=$exit_code"
    } >>"$OUT/meta.txt"
    if [[ "$exit_code" -eq 0 ]]; then
        echo "PASS — suite=$SUITE — artifacts=$OUT" >"$OUT/summary.txt"
    else
        echo "FAIL — suite=$SUITE — inspect $OUT/console.txt and $OUT/junit.xml" >"$OUT/summary.txt"
    fi
}

if [[ ! -x "$TESTS_DIR/vendor/bin/phpunit" ]]; then
    echo "ERROR: run 'composer install --working-dir=tests' first." | tee "$OUT/console.txt"
    finish 127
    exit 127
fi

if [[ ${#SERVICES[@]} -gt 0 ]]; then
    PROFILE=db
    [[ "$SUITE" == "integration-http2" ]] && PROFILE=http
    echo "Validating the root Compose contract (profile=$PROFILE)..." | tee -a "$OUT/console.txt"
    if ! docker compose --project-directory "$ROOT" --profile "$PROFILE" config --quiet 2>&1 | tee -a "$OUT/console.txt"; then
        finish 78
        exit 78
    fi

    echo "Starting approved test services: ${SERVICES[*]}" | tee -a "$OUT/console.txt"
    if ! docker compose --project-directory "$ROOT" --profile "$PROFILE" up -d --wait "${SERVICES[@]}" 2>&1 | tee -a "$OUT/console.txt"; then
        docker compose --project-directory "$ROOT" --profile "$PROFILE" ps >"$OUT/docker-compose-ps.txt" 2>&1 || true
        finish 69
        exit 69
    fi
    docker compose --project-directory "$ROOT" --profile "$PROFILE" ps >"$OUT/docker-compose-ps.txt" 2>&1 || true

    if [[ "$SUITE" == "integration-http2" ]]; then
        set +e
        docker compose --project-directory "$ROOT" --profile http run --rm \
            -e Y2_HTTP_RESULTS_DIR=/results \
            -v "$OUT:/results" \
            http-client 2>&1 | tee -a "$OUT/console.txt"
        EXIT=${PIPESTATUS[0]}
        set -e
        docker compose --project-directory "$ROOT" --profile http ps >"$OUT/docker-compose-ps.txt" 2>&1 || true
        finish "$EXIT"
        exit "$EXIT"
    fi
fi

cd "$TESTS_DIR"
PHPUNIT_ARGS=(
    -c phpunit.xml
    --testsuite "$SUITE"
    --log-junit "$OUT/junit.xml"
    --testdox-text "$OUT/testdox.txt"
)

set +e
php -d opcache.enable_cli=0 ./vendor/bin/phpunit "${PHPUNIT_ARGS[@]}" 2>&1 | tee -a "$OUT/console.txt"
EXIT=${PIPESTATUS[0]}
set -e

finish "$EXIT"
exit "$EXIT"
