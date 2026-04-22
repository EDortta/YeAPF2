<?php declare(strict_types=1);

namespace YeAPF\Connection\DB\Driver;

interface SchemaInspectorInterface
{
    public const CANONICAL_COLUMN_METADATA_FIELDS = [
        'column_name',
        'column_default',
        'is_nullable',
        'data_type',
        'character_maximum_length',
        'numeric_precision',
        'numeric_scale',
        'is_primary',
        'is_unique',
        'is_required',
    ];

    public function tableExists(string $tablename, ?string $schemaname = null): bool;

    public function columnExists(string $tablename, string $columnname, ?string $schemaname = null): bool;

    /**
     * Return canonical metadata for a single column.
     *
     * Expected keys for ORM consumers are listed in
     * self::CANONICAL_COLUMN_METADATA_FIELDS.
     *
     * @return array<string,mixed>|null
     */
    public function columnDefinition(string $tablename, string $columnname, ?string $schemaname = null): ?array;

    /**
     * Return a canonical metadata list for all table columns.
     *
     * @return array<int,array<string,mixed>>
     */
    public function columns(string $tablename, ?string $schemaname = null): array;
}
