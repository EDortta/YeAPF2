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

    public function getSchemaName(): string
    {
        return 'public';
    }

    public function getDriver(): DBDriverInterface
    {
        return new class implements DBDriverInterface {
            /** @var array<int, array{id:int,name:string}> */
            private array $rows = [
                1 => ['id' => 1, 'name' => 'Ana'],
                2 => ['id' => 2, 'name' => 'Bia'],
            ];
            /** @var array<int, array{id:int,name:string}>|null */
            private ?array $snapshot = null;

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
                if (isset($params['id'], $params['name'])) {
                    $id = (int) $params['id'];
                    $this->rows[$id] = ['id' => $id, 'name' => (string) $params['name']];
                }
                return 1;
            }

            public function fetchOne(string $sql, array $params = []): ?array
            {
                $id = (int) ($params['id'] ?? 1);
                return $this->rows[$id] ?? null;
            }

            public function fetchAll(string $sql, array $params = []): array
            {
                return array_values($this->rows);
            }

            public function beginTransaction(): void
            {
                $this->snapshot = $this->rows;
            }

            public function commit(): void
            {
                $this->snapshot = null;
            }

            public function rollBack(): void
            {
                if ($this->snapshot !== null) {
                    $this->rows = $this->snapshot;
                    $this->snapshot = null;
                }
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
                return in_array($tablename, ['yeapf2_contract_users', 'yeapf2_contract_plan'], true);
            }

            public function columnExists(string $tablename, string $columnname, ?string $schemaname = null): bool
            {
                $columns = $tablename === 'yeapf2_contract_plan' ? ['id', 'label'] : ['id', 'name'];
                return $this->tableExists($tablename, $schemaname) && in_array($columnname, $columns, true);
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
                if ('yeapf2_contract_users' !== $tablename) {
                    return [];
                }

                return [
                    $this->columnDefinition('yeapf2_contract_users', 'id', $schemaname),
                    $this->columnDefinition('yeapf2_contract_users', 'name', $schemaname),
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
