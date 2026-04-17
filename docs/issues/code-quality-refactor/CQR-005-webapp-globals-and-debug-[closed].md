# CQR-005 — yeapf-webapp.php: global variables and live debug scaffolding

## File
`src/webapp/yeapf-webapp.php`

## Problems

### 1. global $yAnalyzer in renderPage() and go()
Two static methods depend on `global $yAnalyzer`. This is a hidden runtime coupling that violates module isolation rules.

### 2. Live var_dump + die() in getRouteHandlerDefinition()
`getRouteHandlerDefinition()` has `$debug = false` with multiple `var_dump()` and a `die()` call behind it (lines 303–330). These must not exist in production code even behind a flag — the flag will get flipped in debugging and left on.

### 3. setRouteHandler() is 83 lines
Contains two deeply nested branches (typed parameters vs plain paths) with complex regex manipulation. Candidate for extraction into `registerTypedRoute()` and `registerSimpleRoute()` private methods.

## What to do
- Remove `global $yAnalyzer` — inject or access via a proper static accessor without global
- Delete the entire `$debug` block in `getRouteHandlerDefinition()` including `var_dump`, `echo`, and `die()`
- Extract the two branches of `setRouteHandler()` into named private methods
