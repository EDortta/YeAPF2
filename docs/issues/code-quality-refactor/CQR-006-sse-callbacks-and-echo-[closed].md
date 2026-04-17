# CQR-006 — yeapf-sse-service.php: callback structure and echo statements

## File
`src/service/yeapf-sse-service.php`

## Problems

### 1. start() is 125 lines
All event handler registrations are inline anonymous closures with business logic embedded. Same structural problem as `yeapf-http2-service.php`.

### 2. echo statements in production path
`echo ">>> New client connection"`, `echo "<<< Client connection closed"`, `echo 'Current clientID: ...'`, `echo "New client request"`, `echo "[ sending event: ...]"`, `echo "Client request closed"` — these are raw stdout writes, not logged. In a Swoole service they go nowhere useful and will never be seen in production.

### 3. Nested go(function(){}) inside Request callback
The coroutine inside the Request handler is 30+ lines of loop + event dispatch logic, deeply nested inside an already-complex callback.

## What to do
- Replace all `echo` with `_log()` or `yLogger::log()` at appropriate levels
- Extract the coroutine body into a named private method: `runClientEventLoop(Response $response, string $clientId, $server): void`
- Extract the Request callback body into a named private method: `handleRequest(Request $request, Response $response): void`
- `start()` should register callbacks that are one-liners delegating to named methods
