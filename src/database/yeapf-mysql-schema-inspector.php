<?php declare(strict_types=1);

namespace YeAPF\Connection\DB;

use YeAPF\Connection\DB\Driver\SchemaInspectorInterface;

final class MySQLSchemaInspector implements SchemaInspectorInterface
{
    private PDOConnection $connection;
    private string $defaultSchema;

    public function __construct(PDOConnection $connection, string $defaultSchema)
    {
        $this->connection = $connection;
        $this->defaultSchema = $defaultSchema;
    }

    private function normalizeSchema(?string $schemaname): string
    {
        if (null === $schemaname || '' === trim($schemaname)) {
            $schemaname = $this->defaultSchema;
        }

        return (string) $schemaname;
    }

    public function tableExists(string $tablename, ?string $schemaname = null): bool
    {
        $sql = 'select exists(select 1 from information_schema.tables where table_schema=:schemaname and table_name=:tablename) as `exists`';
        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename),
        ];

        $ret = $this->connection->queryAndFetch($sql, $params);
        return (bool) (is_array($ret) && ($ret['exists'] ?? false));
    }

    public function columnExists(string $tablename, string $columnname, ?string $schemaname = null): bool
    {
        $sql = 'select column_name from information_schema.columns where table_schema=:schemaname and table_name=:tablename and column_name=:columnname';
        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename),
            'columnname' => strtolower($columnname),
        ];

        $ret = $this->connection->queryAndFetch($sql, $params);
        if (!is_array($ret)) {
            return false;
        }

        return strcasecmp((string) ($ret['column_name'] ?? ''), strtolower($columnname)) === 0;
    }

    public function columnDefinition(string $tablename, string $columnname, ?string $schemaname = null): ?array
    {
        $sql = "SELECT c.column_name, c.column_default, c.is_nullable, c.data_type, c.character_maximum_length,
                       c.numeric_precision, c.numeric_scale,
                       CASE WHEN tc.constraint_type = 'PRIMARY KEY' THEN 1 ELSE 0 END AS is_primary,
                       CASE WHEN (
                           SELECT COUNT(1)
                           FROM information_schema.statistics s
                           WHERE s.table_schema = c.table_schema
                             AND s.table_name = c.table_name
                             AND s.column_name = c.column_name
                             AND s.non_unique = 0
                       ) > 0 THEN 1 ELSE 0 END AS is_unique,
                       CASE WHEN c.is_nullable = 'NO' THEN 1 ELSE 0 END AS is_required
                FROM information_schema.columns c
                LEFT JOIN information_schema.key_column_usage k
                    ON c.table_schema = k.table_schema
                   AND c.table_name = k.table_name
                   AND c.column_name = k.column_name
                LEFT JOIN information_schema.table_constraints tc
                    ON k.constraint_name = tc.constraint_name
                   AND k.table_schema = tc.table_schema
                   AND k.table_name = tc.table_name
                   AND tc.constraint_type = 'PRIMARY KEY'
                WHERE c.table_schema = :schemaname
                  AND c.table_name = :tablename
                  AND c.column_name = :columnname";

        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename),
            'columnname' => strtolower($columnname),
        ];

        $row = $this->connection->queryAndFetch($sql, $params);
        if (!is_array($row)) {
            return null;
        }

        return $this->normalizeColumnMetadata($row);
    }

    public function columns(string $tablename, ?string $schemaname = null): array
    {
        $sql = "SELECT c.column_name, c.column_default, c.is_nullable, c.data_type, c.character_maximum_length,
                       c.numeric_precision, c.numeric_scale,
                       CASE WHEN tc.constraint_type = 'PRIMARY KEY' THEN 1 ELSE 0 END AS is_primary,
                       CASE WHEN (
                           SELECT COUNT(1)
                           FROM information_schema.statistics s
                           WHERE s.table_schema = c.table_schema
                             AND s.table_name = c.table_name
                             AND s.column_name = c.column_name
                             AND s.non_unique = 0
                       ) > 0 THEN 1 ELSE 0 END AS is_unique,
                       CASE WHEN c.is_nullable = 'NO' THEN 1 ELSE 0 END AS is_required
                FROM information_schema.columns c
                LEFT JOIN information_schema.key_column_usage k
                    ON c.table_schema = k.table_schema
                   AND c.table_name = k.table_name
                   AND c.column_name = k.column_name
                LEFT JOIN information_schema.table_constraints tc
                    ON k.constraint_name = tc.constraint_name
                   AND k.table_schema = tc.table_schema
                   AND k.table_name = tc.table_name
                   AND tc.constraint_type = 'PRIMARY KEY'
                WHERE c.table_schema = :schemaname
                  AND c.table_name = :tablename";

        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename),
        ];

        $ret = [];
        $q = $this->connection->query($sql, $params);
        while ($d = $q->fetch()) {
            $ret[] = $this->normalizeColumnMetadata($d);
        }

        return $ret;
    }

    /**
     * @param array<string,mixed> $columnMetadata
     * @return array<string,mixed>
     */
    private function normalizeColumnMetadata(array $columnMetadata): array
    {
        $dataType = strtolower((string) ($columnMetadata['data_type'] ?? ''));
        if ('varchar' === $dataType) {
            $dataType = 'character varying';
        } elseif ('tinyint' === $dataType) {
            $dataType = 'boolean';
        }

        return [
            'column_name' => strtolower((string) ($columnMetadata['column_name'] ?? '')),
            'column_default' => $columnMetadata['column_default'] ?? null,
            'is_nullable' => strtoupper((string) ($columnMetadata['is_nullable'] ?? 'YES')),
            'data_type' => $dataType,
            'character_maximum_length' => $columnMetadata['character_maximum_length'] ?? null,
            'numeric_precision' => $columnMetadata['numeric_precision'] ?? null,
            'numeric_scale' => $columnMetadata['numeric_scale'] ?? null,
            'is_primary' => (int) ($columnMetadata['is_primary'] ?? 0),
            'is_unique' => (int) ($columnMetadata['is_unique'] ?? 0),
            'is_required' => (int) ($columnMetadata['is_required'] ?? (($columnMetadata['is_nullable'] ?? 'YES') === 'NO' ? 1 : 0)),
        ];
    }
}
