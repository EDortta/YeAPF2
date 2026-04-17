# H2S-002 — yeapf-http2-service.php: refactor Request callback

## File
`src/service/yeapf-http2-service.php`

## Problem
The `'Request'` callback uses IIFE-in-closure style, depends on `global $currentURI`, and contains commented debug blocks. This reduces readability and increases coupling.

## What to do
- Extract request handling into dedicated private methods
- Remove `global $currentURI` usage in favor of explicit parameter/context flow
- Remove commented debug code and legacy inline debug logic

## Verification
- Existing request handling tests keep passing
- Request routing behavior and error handling remain unchanged
