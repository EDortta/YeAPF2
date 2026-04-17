# H2S-001 — yeapf-http2-service.php: refactor getAsOpenAPIJSON()

## File
`src/service/yeapf-http2-service.php`

## Problem
`getAsOpenAPIJSON()` has grown into a long multi-responsibility method (about 175 lines) that mixes schema extraction, response assembly, and serialization.

## What to do
- Split `getAsOpenAPIJSON()` into focused internal builders (paths, components, security, tags)
- Keep output compatibility with current OpenAPI JSON shape
- Remove dead/commented debug blocks in this path

## Verification
- Existing HTTP2/OpenAPI tests pass
- Output for a representative service remains semantically equivalent
