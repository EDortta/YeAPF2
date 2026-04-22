#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

echo "[1/4] Starting docker databases"
cd tests/db-environments
docker compose up -d
cd "$ROOT_DIR"

echo "[2/4] Waiting for MySQL health status"
for _ in $(seq 1 90); do
  status="$(docker inspect -f '{{.State.Health.Status}}' yeapf2-test-mysql 2>/dev/null || true)"
  if [[ "$status" == "healthy" ]]; then
    break
  fi
  sleep 1
done

status="$(docker inspect -f '{{.State.Health.Status}}' yeapf2-test-mysql 2>/dev/null || true)"
if [[ "$status" != "healthy" ]]; then
  echo "MySQL container did not become healthy in time (status=$status)" >&2
  exit 1
fi

echo "[3/4] Running ORM docker flow test (MySQL)"
tests/vendor/bin/phpunit -c phpunit.xml tests/ORMDockerMySQLFlowTest.php

echo "[4/4] Done"
