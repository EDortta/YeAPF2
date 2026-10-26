<?php

declare(strict_types=1);

namespace YeAPF\Connection\DB\Driver;

use InvalidArgumentException;

abstract class SqlDDLSynthesizer implements DDLSynthesizerInterface
{
    abstract protected function engine(): string;

    protected function quote(string $identifier): string
    {
        if ($identifier === '' || str_contains($identifier, "\0")) {
            throw new InvalidArgumentException('Database identifier cannot be empty or contain NUL.');
        }
        $quote = $this->engine() === 'mysql' ? '`' : '"';
        return $quote . str_replace($quote, $quote . $quote, $identifier) . $quote;
    }

    /** @param array<string, mixed> $manifestDiff */
    public function normalizeManifestDiff(array $manifestDiff): array
    {
        $operations = array_values($manifestDiff['operations'] ?? []);
        foreach ($operations as $operation) {
            if (!is_array($operation) || !is_string($operation['type'] ?? null)) {
                throw new InvalidArgumentException('Every DDL operation requires a string type.');
            }
        }
        return ['operations' => $operations, 'metadata' => (array) ($manifestDiff['metadata'] ?? [])];
    }

    public function synthesize(array $manifestDiff): array
    {
        $normalized = $this->normalizeManifestDiff($manifestDiff);
        $statements = [];
        foreach ($normalized['operations'] as $operation) {
            $statements[] = [
                'kind' => $operation['type'],
                'sql' => $this->statement($operation),
                'rollback_sql' => $this->rollback($operation),
                'idempotent' => (bool) ($operation['if_not_exists'] ?? true),
                'metadata' => ['engine' => $this->engine(), 'table' => $operation['table'] ?? null],
            ];
        }

        return [
            'statements' => $statements,
            'idempotent' => !in_array(false, array_column($statements, 'idempotent'), true),
            'fingerprint' => hash('sha256', json_encode([$this->engine(), $normalized], JSON_THROW_ON_ERROR)),
            'metadata' => array_merge($normalized['metadata'], ['engine' => $this->engine()]),
        ];
    }

    public function isValidPlan(array $plan): bool
    {
        if (
            !is_array($plan['statements'] ?? null)
            || !is_bool($plan['idempotent'] ?? null)
            || !is_string($plan['fingerprint'] ?? null)
            || !is_array($plan['metadata'] ?? null)
        ) {
            return false;
        }
        foreach ($plan['statements'] as $statement) {
            if (
                !is_array($statement)
                || !is_string($statement['kind'] ?? null)
                || !is_string($statement['sql'] ?? null)
                || !is_bool($statement['idempotent'] ?? null)
                || !is_array($statement['metadata'] ?? null)
            ) {
                return false;
            }
        }
        return true;
    }

    /** @param array<string, mixed> $operation */
    private function statement(array $operation): string
    {
        return match ($operation['type']) {
            self::OP_CREATE_TABLE => $this->createTable($operation),
            self::OP_ALTER_TABLE => $this->alterTable($operation),
            self::OP_CREATE_ENUM => $this->createEnum($operation),
            self::OP_ADD_FOREIGN_KEY => $this->addForeignKey($operation),
            self::OP_CREATE_INDEX => $this->createIndex($operation),
            default => throw new InvalidArgumentException('Unsupported DDL operation: ' . $operation['type']),
        };
    }

    /** @param array<string, mixed> $operation */
    private function createTable(array $operation): string
    {
        $columns = $operation['columns'] ?? [['name' => 'id', 'type' => 'integer', 'primary' => true]];
        if (!is_array($columns) || $columns === []) {
            throw new InvalidArgumentException('create_table requires at least one column.');
        }
        $definitions = [];
        foreach ($columns as $column) {
            if (!is_array($column) || !isset($column['name'], $column['type'])) {
                throw new InvalidArgumentException('Every table column requires name and type.');
            }
            $definition = $this->quote((string) $column['name']) . ' ' . $this->type((string) $column['type'], $column);
            if (($column['nullable'] ?? false) !== true) {
                $definition .= ' NOT NULL';
            }
            if (array_key_exists('default', $column)) {
                $definition .= ' DEFAULT ' . $this->literal($column['default']);
            }
            if (($column['primary'] ?? false) === true) {
                $definition .= ' PRIMARY KEY';
            }
            if (($column['unique'] ?? false) === true) {
                $definition .= ' UNIQUE';
            }
            $definitions[] = $definition;
        }
        $if = ($operation['if_not_exists'] ?? true) ? ' IF NOT EXISTS' : '';
        return 'CREATE TABLE' . $if . ' ' . $this->qualified($operation) . ' (' . implode(', ', $definitions) . ')';
    }

    /** @param array<string, mixed> $operation */
    private function alterTable(array $operation): string
    {
        $changes = $operation['changes'] ?? [];
        if (!is_array($changes) || $changes === []) {
            throw new InvalidArgumentException('alter_table requires changes.');
        }
        $parts = [];
        foreach ($changes as $change) {
            if (($change['action'] ?? null) !== 'add_column') {
                throw new InvalidArgumentException('Only add_column is supported by the canonical alter operation.');
            }
            $parts[] = 'ADD COLUMN ' . (($operation['if_not_exists'] ?? true) ? 'IF NOT EXISTS ' : '')
                . $this->quote((string) $change['name']) . ' ' . $this->type((string) $change['type'], $change);
        }
        return 'ALTER TABLE ' . $this->qualified($operation) . ' ' . implode(', ', $parts);
    }

    /** @param array<string, mixed> $operation */
    private function createEnum(array $operation): string
    {
        $values = array_map(fn ($value): string => $this->literal($value), (array) ($operation['enum_values'] ?? []));
        if ($values === [] || empty($operation['name'])) {
            throw new InvalidArgumentException('create_enum requires name and enum_values.');
        }
        if ($this->engine() === 'mysql') {
            return 'SELECT ' . implode(', ', $values) . ' /* enum ' . $this->quote((string) $operation['name']) . ' */';
        }
        return 'DO $$ BEGIN CREATE TYPE ' . $this->quote((string) $operation['name']) . ' AS ENUM ('
            . implode(', ', $values) . '); EXCEPTION WHEN duplicate_object THEN NULL; END $$';
    }

    /** @param array<string, mixed> $operation */
    private function addForeignKey(array $operation): string
    {
        $foreign = (array) ($operation['foreign_key'] ?? []);
        $columns = array_map(fn ($item): string => $this->quote((string) $item), (array) ($foreign['columns'] ?? []));
        $references = array_map(
            fn ($item): string => $this->quote((string) $item),
            (array) ($foreign['reference_columns'] ?? ['id'])
        );
        return 'ALTER TABLE ' . $this->qualified($operation)
            . ' ADD CONSTRAINT ' . $this->quote((string) ($foreign['name'] ?? $operation['name'] ?? 'fk'))
            . ' FOREIGN KEY (' . implode(', ', $columns) . ') REFERENCES '
            . $this->quote((string) ($foreign['reference_table'] ?? '')) . ' (' . implode(', ', $references) . ')';
    }

    /** @param array<string, mixed> $operation */
    private function createIndex(array $operation): string
    {
        $index = (array) ($operation['index'] ?? []);
        $columns = array_map(fn ($item): string => $this->quote((string) $item), (array) ($index['columns'] ?? []));
        $unique = ($index['unique'] ?? false) ? 'UNIQUE ' : '';
        $if = ($operation['if_not_exists'] ?? true) ? 'IF NOT EXISTS ' : '';
        $name = (string) ($index['name'] ?? $operation['name'] ?? 'idx');
        return 'CREATE ' . $unique . 'INDEX ' . $if . $this->quote($name)
            . ' ON ' . $this->qualified($operation) . ' (' . implode(', ', $columns) . ')';
    }

    /** @param array<string, mixed> $operation */
    private function qualified(array $operation): string
    {
        $table = $this->quote((string) ($operation['table'] ?? ''));
        $schema = $operation['schema'] ?? null;
        return is_string($schema) && $schema !== '' ? $this->quote($schema) . '.' . $table : $table;
    }

    /** @param array<string, mixed> $column */
    private function type(string $type, array $column): string
    {
        return match (strtolower($type)) {
            'string', 'varchar' => 'VARCHAR(' . max(1, (int) ($column['length'] ?? 255)) . ')',
            'text' => 'TEXT',
            'integer', 'int' => 'INTEGER',
            'bigint' => 'BIGINT',
            'boolean', 'bool' => $this->engine() === 'mysql' ? 'BOOLEAN' : 'BOOLEAN',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'json' => $this->engine() === 'pgsql' ? 'JSONB' : 'JSON',
            default => throw new InvalidArgumentException('Unsupported canonical column type: ' . $type),
        };
    }

    private function literal(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    /** @param array<string, mixed> $operation */
    private function rollback(array $operation): ?string
    {
        return match ($operation['type']) {
            self::OP_CREATE_TABLE => 'DROP TABLE IF EXISTS ' . $this->qualified($operation),
            self::OP_CREATE_INDEX => 'DROP INDEX IF EXISTS ' . $this->quote(
                (string) (($operation['index']['name'] ?? null) ?: ($operation['name'] ?? 'idx'))
            ),
            self::OP_CREATE_ENUM => $this->engine() === 'pgsql'
                ? 'DROP TYPE IF EXISTS ' . $this->quote((string) ($operation['name'] ?? '')) : null,
            default => null,
        };
    }
}
