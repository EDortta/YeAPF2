#!/usr/bin/env sh
set -eu

RESULTS_DIR="${Y2_HTTP_RESULTS_DIR:-/results}"
mkdir -p "$RESULTS_DIR"

php -r 'echo PHP_VERSION, PHP_EOL;' >"$RESULTS_DIR/php-version.txt"
curl --version >"$RESULTS_DIR/curl-version.txt"
/workspace/y2/tests/http-environment/wait-for-http.sh \
  "${Y2_HTTP_BASE_URL:-https://caddy-http:8443}" \
  "${Y2_HTTP_CA_FILE:-/caddy-data/pki/authorities/local/root.crt}"

curl --silent --show-error --http2 --cacert "${Y2_HTTP_CA_FILE:-/caddy-data/caddy/pki/authorities/local/root.crt}" \
  --output /dev/null --write-out '%{http_version}\n' \
  "${Y2_HTTP_BASE_URL:-https://caddy-http:8443}/__health" >"$RESULTS_DIR/negotiated-http-version.txt"

cd /workspace/y2/tests
set +e
php -d opcache.enable_cli=0 /workspace/y2/tests/vendor/bin/phpunit \
  -c phpunit.http2.xml \
  --testsuite integration-http2 \
  --log-junit "$RESULTS_DIR/junit.xml" \
  --testdox-text "$RESULTS_DIR/testdox.txt" \
  >"$RESULTS_DIR/phpunit-console.txt" 2>&1
code=$?
set -e
cat "$RESULTS_DIR/phpunit-console.txt"
exit "$code"
