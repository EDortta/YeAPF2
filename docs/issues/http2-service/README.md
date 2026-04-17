# Epic: http2-service

## Goal
Simplify HTTP2 service definitions and make them feel as natural as WebApp routes, with automatic OpenAPI generation and proper middleware support.

## Motivation
Current HTTP2 service definitions are more complex than they need to be. Middleware for auth, logging, and rate limiting must be wired manually. There is no automatic API documentation output.

## Scope
- Simplify service/route definition API to match WebApp ergonomics
- Automatic OpenAPI spec generation from service definitions
- Middleware layer for: authentication, logging, rate limiting
- Improved WebSocket and SSE support for real-time use cases
- Client SDK improvements for consuming YeAPF2 services

## Agent
`http2-service-agent` → `code-quality-agent` for readability review → `psr-compliance-agent` for style conformance

## Issues
- [H2S-001-refactor-get-as-openapi-json.md](./H2S-001-refactor-get-as-openapi-json-[PR].md)
- [H2S-002-refactor-request-callback.md](./H2S-002-refactor-request-callback-[opened].md)
- [H2S-003-middleware-pipeline.md](./H2S-003-middleware-pipeline-[opened].md)
- [H2S-004-openapi-auto-generation.md](./H2S-004-openapi-auto-generation-[opened].md)
- [H2S-005-service-definition-simplification.md](./H2S-005-service-definition-simplification-[opened].md)
