# CQR-009 — yParser.php: get() method is 199 lines

## File
`src/misc/yParser.php`

## Problem
`get()` (line 188–387) is 199 lines — the longest single method in the codebase after `checkConstraint()`. It handles tokenization for: numbers, strings (single/double quote), comments (line and block), operators, symbols, macros, and literals — all in one sequential if/elseif chain with deep nesting.

`get_html()` (line 419–491) is 72 lines — also over the limit.

## What to do
- Extract each token-type branch into a named private method:
  - `readNumber(string $c): array`
  - `readString(string $delimiter): array`
  - `readLineComment(): array`
  - `readBlockComment(): array`
  - `readOperator(string $c): array`
  - `readMacro(string $c): array`
- `get()` becomes a dispatcher: read next char, call the appropriate reader, return result
- `get_html()` — extract the script-mode branch into `readHtmlScriptToken(): array`
