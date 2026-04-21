#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

echo "[1/3] Starting docker databases"
cd tests/db-environments
docker compose up -d
cd "$ROOT_DIR"

echo "[2/4] Waiting for PostgreSQL health status"
for _ in $(seq 1 60); do
  status="$(docker inspect -f '{{.State.Health.Status}}' yeapf2-test-postgres 2>/dev/null || true)"
  if [[ "$status" == "healthy" ]]; then
    break
  fi
  sleep 1
done

status="$(docker inspect -f '{{.State.Health.Status}}' yeapf2-test-postgres 2>/dev/null || true)"
if [[ "$status" != "healthy" ]]; then
  echo "PostgreSQL container did not become healthy in time (status=$status)" >&2
  exit 1
fi

echo "[3/4] Running ORM docker flow test"
tests/vendor/bin/phpunit -c phpunit.xml tests/ORMDockerPostgresFlowTest.php

echo "[4/4] Done"
