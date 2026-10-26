#!/usr/bin/env sh
set -eu

BASE_URL="${1:-https://caddy-http:8443}"
CA_FILE="${2:-/caddy-data/caddy/pki/authorities/local/root.crt}"
TIMEOUT="${Y2_HTTP_WAIT_TIMEOUT:-30}"
start="$(date +%s)"

while :; do
  now="$(date +%s)"
  if [ $((now - start)) -ge "$TIMEOUT" ]; then
    echo "HTTP test application did not become healthy within ${TIMEOUT}s." >&2
    echo "Expected endpoint: $BASE_URL/__health" >&2
    exit 70
  fi

  if [ -r "$CA_FILE" ] && curl --silent --show-error --fail --http2 \
      --cacert "$CA_FILE" --max-time 2 "$BASE_URL/__health" >/dev/null 2>&1; then
    exit 0
  fi
  sleep 1
done
