<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class DDLSynthesizerInterfaceContractTest extends TestCase
{
    public function testDDLSynthesizerInterfaceMethodSignatures(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Connection\DB\Driver\DDLSynthesizerInterface::class);

        $this->assertMethodSignature($reflection, 'normalizeManifestDiff', 1, 'array', ['manifestDiff' => 'array']);
        $this->assertMethodSignature($reflection, 'synthesize', 1, 'array', ['manifestDiff' => 'array']);
        $this->assertMethodSignature($reflection, 'isValidPlan', 1, 'bool', ['plan' => 'array']);
    }

    public function testSynthesisOutputShapeAcrossCanonicalOperations(): void
    {
        $synthesizer = new FixtureDDLSynthesizer();

        $manifestDiff = [
            'operations' => [
                ['type' => \YeAPF\Connection\DB\Driver\DDLSynthesizerInterface::OP_CREATE_TABLE, 'schema' => 'public', 'table' => 'customers', 'if_not_exists' => true],
                ['type' => \YeAPF\Connection\DB\Driver\DDLSynthesizerInterface::OP_ALTER_TABLE, 'schema' => 'public', 'table' => 'customers', 'changes' => [['action' => 'add_column', 'name' => 'status']]],
                ['type' => \YeAPF\Connection\DB\Driver\DDLSynthesizerInterface::OP_CREATE_ENUM, 'name' => 'customer_status', 'enum_values' => ['active', 'inactive']],
                ['type' => \YeAPF\Connection\DB\Driver\DDLSynthesizerInterface::OP_ADD_FOREIGN_KEY, 'table' => 'orders', 'foreign_key' => ['name' => 'fk_orders_customer']],
                ['type' => \YeAPF\Connection\DB\Driver\DDLSynthesizerInterface::OP_CREATE_INDEX, 'table' => 'orders', 'index' => ['name' => 'idx_orders_customer_id']],
            ],
            'metadata' => ['source' => 'contract-test'],
        ];

        $plan = $synthesizer->synthesize($manifestDiff);

        $this->assertTrue($synthesizer->isValidPlan($plan));
        $this->assertCount(5, $plan['statements']);
        $this->assertSame('contract-test', $plan['metadata']['source']);

        foreach ($plan['statements'] as $statement) {
            $this->assertArrayHasKey('kind', $statement);
            $this->assertArrayHasKey('sql', $statement);
            $this->assertArrayHasKey('idempotent', $statement);
            $this->assertArrayHasKey('metadata', $statement);
            $this->assertIsString($statement['sql']);
            $this->assertNotSame('', trim($statement['sql']));
        }

        $this->assertTrue($plan['idempotent']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $plan['fingerprint']);
    }

    /**
     * @param array<string,string> $expectedParameterTypes
     */
    private function assertMethodSignature(
        ReflectionClass $reflection,
        string $methodName,
        int $expectedParameterCount,
        string $expectedReturnType,
        array $expectedParameterTypes = []
    ): void {
        $this->assertTrue($reflection->hasMethod($methodName));

        $method = $reflection->getMethod($methodName);
        $this->assertCount($expectedParameterCount, $method->getParameters());
        $this->assertSame($expectedReturnType, $this->normalizeTypeName($method->getReturnType()));

        $parametersByName = [];
        foreach ($method->getParameters() as $parameter) {
            $parametersByName[$parameter->getName()] = $parameter;
        }

        foreach ($expectedParameterTypes as $parameterName => $expectedType) {
            $this->assertArrayHasKey($parameterName, $parametersByName);
            $this->assertSame($expectedType, $this->normalizeTypeName($parametersByName[$parameterName]->getType()));
        }
    }

    private function normalizeTypeName(?ReflectionType $type): string
    {
        if (null === $type) {
            return 'mixed';
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if ($type->allowsNull() && 'mixed' !== $name && 'null' !== $name && 'void' !== $name) {
                return '?' . $name;
            }

            return $name;
        }

        return (string) $type;
    }
}

final class FixtureDDLSynthesizer implements \YeAPF\Connection\DB\Driver\DDLSynthesizerInterface
{
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
            $kind = (string) ($operation['type'] ?? 'unknown');
            $statements[] = [
                'kind' => $kind,
                'sql' => '-- ' . $kind,
                'rollback_sql' => null,
                'idempotent' => (bool) ($operation['if_not_exists'] ?? true),
                'metadata' => [
                    'table' => $operation['table'] ?? null,
                    'schema' => $operation['schema'] ?? null,
                ],
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
            if (!is_array($statement)) {
                return false;
            }

            if (!isset($statement['kind'], $statement['sql'], $statement['idempotent'], $statement['metadata'])) {
                return false;
            }

            if (!is_string($statement['kind']) || !is_string($statement['sql']) || !is_bool($statement['idempotent']) || !is_array($statement['metadata'])) {
                return false;
            }
        }

        return true;
    }
}
