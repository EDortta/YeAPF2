<?php declare(strict_types=1);

namespace YeAPF\Connection\DB;

final class PostgreSQLSchemaInspector
{
    private PDOConnection $connection;
    private string $defaultSchema;

    public function __construct(PDOConnection $connection, string $defaultSchema = 'public')
    {
        $this->connection = $connection;
        $this->defaultSchema = $defaultSchema;
    }

    private function normalizeSchema(?string $schemaname): string
    {
        if (null === $schemaname || '' === trim($schemaname)) {
            $schemaname = $this->defaultSchema;
        }

        return strtolower((string) $schemaname);
    }

    public function tableExists(string $tablename, ?string $schemaname = null): bool
    {
        $sql = 'select exists(select 1 from pg_tables where tablename=:tablename and schemaname=:schemaname)';
        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename)
        ];

        $ret = $this->connection->queryAndFetch($sql, $params);
        return (bool) (is_array($ret) && ($ret['exists'] ?? false));
    }

    public function columnDefinition(string $tablename, string $columnname, ?string $schemaname = null): mixed
    {
        $sql = 'select column_name, column_default, is_nullable, data_type, character_maximum_length, numeric_precision, numeric_scale from information_schema.columns where table_schema=:schemaname and table_name=:tablename and column_name=:columnname';
        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename),
            'columnname' => strtolower($columnname)
        ];

        return $this->connection->queryAndFetch($sql, $params);
    }

    public function columnExists(string $tablename, string $columnname, ?string $schemaname = null): bool
    {
        $sql = 'select column_name from information_schema.columns where table_schema=:schemaname and table_name=:tablename and column_name=:columnname';
        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename),
            'columnname' => strtolower($columnname)
        ];

        $ret = $this->connection->queryAndFetch($sql, $params);
        if (false === $ret) {
            return false;
        }

        return strcasecmp((string) ($ret['column_name'] ?? ''), strtolower($columnname)) === 0;
    }

    public function columns(string $tablename, ?string $schemaname = null): array
    {
        $sql = "SELECT c.column_name, c.column_default, c.is_nullable, c.data_type, c.character_maximum_length,
                        c.numeric_precision, c.numeric_scale,
                        CASE WHEN p.constraint_type = 'PRIMARY KEY' THEN 1 ELSE 0 END AS is_primary,
                        CASE WHEN u.constraint_name IS NOT NULL THEN 1 ELSE 0 END AS is_unique,
                        CASE WHEN c.is_nullable = 'NO' THEN 1 ELSE 0 END AS is_required
                    FROM information_schema.columns AS c
                    LEFT JOIN information_schema.key_column_usage AS k ON c.column_name = k.column_name
                        AND c.table_name = k.table_name AND c.table_schema = k.table_schema
                    LEFT JOIN information_schema.table_constraints AS p ON p.constraint_name = k.constraint_name
                        AND p.table_name = k.table_name AND p.table_schema = k.table_schema
                        AND p.constraint_type = 'PRIMARY KEY'
                    LEFT JOIN information_schema.table_constraints AS u ON u.constraint_name = k.constraint_name
                        AND u.table_name = k.table_name AND u.table_schema = k.table_schema
                        AND u.constraint_type = 'UNIQUE'
                    WHERE c.table_schema = :schemaname AND c.table_name = :tablename";
        $params = [
            'schemaname' => $this->normalizeSchema($schemaname),
            'tablename' => strtolower($tablename)
        ];

        $ret = [];
        $q = $this->connection->query($sql, $params);
        while ($d = $q->fetch()) {
            $ret[] = $d;
        }

        return $ret;
    }
}
