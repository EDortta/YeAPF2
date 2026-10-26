# Yet Another PHP Framework

Lastely, it's more than a framework, it's more a way-of-life. (Just a bad joke)

This is a document under construction... forever.

## Test and quality matrix

The deterministic default test command does not start Docker or connect to a database:

```bash
composer install --working-dir=tests
composer --working-dir=tests test
composer --working-dir=tests analyse
composer --working-dir=tests style
```

Database integration uses the root Compose contract at PostgreSQL `127.0.0.1:35432` and MySQL `127.0.0.1:33306`. Real TLS/HTTP2 integration is provided by the root `http` profile with Caddy, PHP-FPM, and an internal cURL/PHPUnit client.

Full commands, suite boundaries, artifacts, and opt-in rules are documented in [`tests/README.testing.md`](tests/README.testing.md). Database operations are documented in [`tests/db-environments/README.md`](tests/db-environments/README.md), and HTTP/2 in [`tests/http-environment/README.md`](tests/http-environment/README.md).

CI runs syntax, unit/contract tests on PHP 8.2, 8.3, and 8.4, PHPStan, scoped PSR-12, coverage, dual-engine database integration, and HTTP/2 TLS integration.
