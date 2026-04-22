<?php declare(strict_types=1);

namespace Tests\Contracts\Bootstrap;

use Tests\Contracts\DriverContractBootstrapInterface;
use YeAPF\Connection\DB\Driver\DBDriverInterface;
use YeAPF\Connection\DB\Driver\DDLSynthesizerInterface;
use YeAPF\Connection\DB\Driver\DriverCapabilities;
use YeAPF\Connection\DB\Driver\SchemaInspectorInterface;

final class StubDriverContractBootstrap implements DriverContractBootstrapInterface
{
    public function getEngineName(): string
    {
        return 'stub';
    }

    public function getDriver(): DBDriverInterface
    {
        return new class implements DBDriverInterface {
            public function getDriverName(): string
            {
                return 'stub';
            }

            public function getDriverVersion(): ?string
            {
                return '1.0';
            }

            public function getCapabilities(): DriverCapabilities
            {
                return new DriverCapabilities([
                    'transactions' => true,
                    'schema_inspection' => true,
                    'ddl_synthesis' => true,
                ]);
            }

            public function execute(string $sql, array $params = []): int
            {
                return 1;
            }

            public function fetchOne(string $sql, array $params = []): ?array
            {
                return ['id' => 1, 'name' => 'Ana'];
            }

            public function fetchAll(string $sql, array $params = []): array
            {
                return [
                    ['id' => 1, 'name' => 'Ana'],
                    ['id' => 2, 'name' => 'Bia'],
                ];
            }

            public function beginTransaction(): void
            {
            }

            public function commit(): void
            {
            }

            public function rollBack(): void
            {
            }

            public function normalizeError(\Throwable $throwable, ?string $sql = null, array $params = []): array
            {
                return [
                    'driver' => 'stub',
                    'sql_state' => null,
                    'driver_code' => $throwable->getCode(),
                    'message' => $throwable->getMessage(),
                    'normalized_code' => 'DB_ERROR',
                    'is_transient' => false,
                    'context' => [
                        'sql' => $sql,
                        'params' => $params,
                    ],
                ];
            }
        };
    }

    public function getSchemaInspector(): SchemaInspectorInterface
    {
        return new class implements SchemaInspectorInterface {
            public function tableExists(string $tablename, ?string $schemaname = null): bool
            {
                return 'users' === $tablename;
            }

            public function columnExists(string $tablename, string $columnname, ?string $schemaname = null): bool
            {
                return 'users' === $tablename && in_array($columnname, ['id', 'name'], true);
            }

            public function columnDefinition(string $tablename, string $columnname, ?string $schemaname = null): ?array
            {
                if (!$this->columnExists($tablename, $columnname, $schemaname)) {
                    return null;
                }

                return [
                    'column_name' => $columnname,
                    'column_default' => null,
                    'is_nullable' => 'NO',
                    'data_type' => 'integer' === $columnname ? 'integer' : 'character varying',
                    'character_maximum_length' => 'name' === $columnname ? 255 : null,
                    'numeric_precision' => 'id' === $columnname ? 32 : null,
                    'numeric_scale' => null,
                    'is_primary' => 'id' === $columnname ? 1 : 0,
                    'is_unique' => 'id' === $columnname ? 1 : 0,
                    'is_required' => 1,
                ];
            }

            public function columns(string $tablename, ?string $schemaname = null): array
            {
                if ('users' !== $tablename) {
                    return [];
                }

                return [
                    $this->columnDefinition('users', 'id', $schemaname),
                    $this->columnDefinition('users', 'name', $schemaname),
                ];
            }
        };
    }

    public function getDDLSynthesizer(): DDLSynthesizerInterface
    {
        return new class implements DDLSynthesizerInterface {
            public function normalizeManifestDiff(array $manifestDiff): array
            {
                return [
                    'operations' => array_values($manifestDiff['operations'] ?? []),
                    'metadata' => (array) ($manifestDiff['metadata'] ?? []),
                ];
            }

            public function synthesize(array $manifestDiff): array
            {
                $normalized = $this->normalizeManifestDiff($manifestDiff);
                $statements = [];

                foreach ($normalized['operations'] as $operation) {
                    $type = (string) ($operation['type'] ?? 'unknown');
                    $table = (string) ($operation['table'] ?? 'unknown');
                    $statements[] = [
                        'kind' => $type,
                        'sql' => '/* ' . $type . ' */ ' . 'CREATE TABLE IF NOT EXISTS ' . $table,
                        'rollback_sql' => null,
                        'idempotent' => (bool) ($operation['if_not_exists'] ?? true),
                        'metadata' => ['table' => $table],
                    ];
                }

                return [
                    'statements' => $statements,
                    'idempotent' => !in_array(false, array_column($statements, 'idempotent'), true),
                    'fingerprint' => sha1(json_encode($normalized, JSON_UNESCAPED_SLASHES) ?: ''),
                    'metadata' => $normalized['metadata'],
                ];
            }

            public function isValidPlan(array $plan): bool
            {
                if (!isset($plan['statements'], $plan['idempotent'], $plan['fingerprint'], $plan['metadata'])) {
                    return false;
                }

                if (!is_array($plan['statements']) || !is_bool($plan['idempotent']) || !is_string($plan['fingerprint']) || !is_array($plan['metadata'])) {
                    return false;
                }

                foreach ($plan['statements'] as $statement) {
                    if (!is_array($statement) || !isset($statement['kind'], $statement['sql'], $statement['idempotent'], $statement['metadata'])) {
                        return false;
                    }
                }

                return true;
            }
        };
    }
}
