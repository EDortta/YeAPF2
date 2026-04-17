# CQR-008 — yeapf-jwt.php: secret key exposed in trace logs

## File
`src/security/yeapf-jwt.php`

## Security Concern
`importToken()` includes:
```php
\_trace("SECRET KEY: $this->secretKey");
```
This writes the JWT secret key to the trace log unconditionally. Any trace log file, log aggregator, or monitoring system will capture the secret.

## Other Problems
- `importToken()` has 18 `_trace()` calls including token payload, expiry, and algorithm details — all in the production path
- `cleanBin()` and `sendToBin()` also have traces that expose token values

## What to do
1. **Immediately**: remove the `SECRET KEY` trace line — this is a security fix, not just cleanup
2. Remove all `_trace()` calls from `importToken()`, `cleanBin()`, `sendToBin()`, `tokenInBin()`
3. Any genuinely needed diagnostics must use `yLogger::log()` at `YeAPF_LOG_DEBUG` level and must never include secret material

## Priority
HIGH — security issue takes precedence over normal refactor scheduling
