<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class DBDriverInterfaceContractTest extends TestCase
{
    public function testDBDriverInterfaceHasExpectedMethodSignatures(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Connection\DB\Driver\DBDriverInterface::class);

        $this->assertMethodSignature($reflection, 'getDriverName', 0, 'string');
        $this->assertMethodSignature($reflection, 'getDriverVersion', 0, '?string');
        $this->assertMethodSignature($reflection, 'getCapabilities', 0, \YeAPF\Connection\DB\Driver\DriverCapabilities::class);
        $this->assertMethodSignature($reflection, 'execute', 2, 'int', ['sql' => 'string', 'params' => 'array']);
        $this->assertMethodSignature($reflection, 'fetchOne', 2, '?array', ['sql' => 'string', 'params' => 'array']);
        $this->assertMethodSignature($reflection, 'fetchAll', 2, 'array', ['sql' => 'string', 'params' => 'array']);
        $this->assertMethodSignature($reflection, 'beginTransaction', 0, 'void');
        $this->assertMethodSignature($reflection, 'commit', 0, 'void');
        $this->assertMethodSignature($reflection, 'rollBack', 0, 'void');
        $this->assertMethodSignature($reflection, 'normalizeError', 3, 'array', ['throwable' => 'Throwable', 'sql' => '?string', 'params' => 'array']);
    }

    public function testDriverCapabilitiesDefaultAndOverrides(): void
    {
        $capabilities = new \YeAPF\Connection\DB\Driver\DriverCapabilities();

        $this->assertTrue($capabilities->isEnabled('transactions'));
        $this->assertTrue($capabilities->isEnabled('prepared_statements'));
        $this->assertFalse($capabilities->isEnabled('ddl_synthesis'));

        $custom = \YeAPF\Connection\DB\Driver\DriverCapabilities::fromArray([
            'ddl_synthesis' => true,
            'json_type' => 1,
            'transactions' => false,
        ]);

        $this->assertTrue($custom->isEnabled('ddl_synthesis'));
        $this->assertTrue($custom->isEnabled('json_type'));
        $this->assertFalse($custom->isEnabled('transactions'));

        $array = $custom->toArray();
        $this->assertArrayHasKey('upsert', $array);
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
            $parameter = $parametersByName[$parameterName];
            $this->assertSame($expectedType, $this->normalizeTypeName($parameter->getType()));
        }
    }

    private function normalizeTypeName(?ReflectionType $type): string
    {
        if (null === $type) {
            return 'mixed';
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if ('Throwable' === $name || '\\Throwable' === $name) {
                $name = 'Throwable';
            }

            if ($type->allowsNull() && 'mixed' !== $name && 'null' !== $name && 'void' !== $name) {
                return '?' . $name;
            }

            return $name;
        }

        return (string) $type;
    }
}
