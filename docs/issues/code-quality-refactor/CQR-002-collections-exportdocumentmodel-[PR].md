# CQR-002 — yeapf-collections.php: split exportDocumentModel()

## File
`src/database/yeapf-collections.php`

## Problem
`exportDocumentModel(int $format)` uses a switch to handle three completely different output formats (JSON, SQL, Protobuf) in a single method. The Protobuf branch alone is ~50 lines with an inline `uasort` closure and a nested switch for type mapping.

## What to do
- Extract each format branch into a named private method:
  - `exportAsJson(): string`
  - `exportAsSql(): string`
  - `exportAsProtobuf(): string`
- `exportDocumentModel()` becomes a 5-line coordinator
- Extract the Protobuf type mapping switch into a private `yeapfTypeTo ProtobufType(string $type): string`

## Related
CQR-001 (clean debug noise first, then refactor structure)
