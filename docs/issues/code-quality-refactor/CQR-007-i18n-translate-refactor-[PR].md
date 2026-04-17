# CQR-007 — i18n.php: translate() method refactor

## File
`src/plugins/i18n.php`

## Problems

### 1. $debug = true hardcoded in production
`translate()` has `$debug = true` at line 109. There are ~30 `_trace()` calls inside the method guarded by `if ($debug)`. These fire in production.

### 2. translate() is a single massive method with a coroutine inside
The method creates a `Coroutine::create()` inline with deeply nested logic for: cache lookup, clone, tagging, original text save, translation API call, and result handling — all inside one closure, all in one method.

### 3. Nesting depth exceeds 5 levels
The combination of foreach → Coroutine::create → if → if → if → more logic creates nesting that is unreadable.

## What to do
- Set `$debug = false` immediately as a stop-gap; then remove the flag and all `_trace()` calls entirely
- Extract coroutine body into a named private method: `translateTag(string $tag, string $scope, string $targetLang, string $DOMText, Channel $channel): void`
- Extract API call into: `callTranslationAPI(string $text, string $targetLang): ?string`
- Extract asset save/load into: `loadCachedTranslation(...)` / `saveCachedTranslation(...)`
