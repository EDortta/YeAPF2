<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class SchemaInspectorInterfaceContractTest extends TestCase
{
    public function testSchemaInspectorInterfaceMethodSignatures(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Connection\DB\Driver\SchemaInspectorInterface::class);

        $this->assertMethodSignature($reflection, 'tableExists', 2, 'bool', ['tablename' => 'string', 'schemaname' => '?string']);
        $this->assertMethodSignature($reflection, 'columnExists', 3, 'bool', ['tablename' => 'string', 'columnname' => 'string', 'schemaname' => '?string']);
        $this->assertMethodSignature($reflection, 'columnDefinition', 3, '?array', ['tablename' => 'string', 'columnname' => 'string', 'schemaname' => '?string']);
        $this->assertMethodSignature($reflection, 'columns', 2, 'array', ['tablename' => 'string', 'schemaname' => '?string']);
    }

    public function testCanonicalMetadataFieldsAreStableForOrm(): void
    {
        $canonicalFields = \YeAPF\Connection\DB\Driver\SchemaInspectorInterface::CANONICAL_COLUMN_METADATA_FIELDS;

        $expectedFields = [
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

        $this->assertSame($expectedFields, $canonicalFields);

        $fixture = [
            'column_name' => 'amount',
            'column_default' => null,
            'is_nullable' => 'NO',
            'data_type' => 'numeric',
            'character_maximum_length' => null,
            'numeric_precision' => 10,
            'numeric_scale' => 2,
            'is_primary' => 0,
            'is_unique' => 0,
            'is_required' => 1,
        ];

        foreach ($canonicalFields as $field) {
            $this->assertArrayHasKey($field, $fixture);
        }

        $context = $this->createMock(\YeAPF\Connection\PersistenceContext::class);
        $documentModel = new \YeAPF\ORM\DocumentModel($context, 'contract_fixture');

        $converter = \Closure::bind(
            static function (\YeAPF\ORM\DocumentModel $model, array $columnDefinition): array {
                return $model->SQLColumnDefinition2Constraint($columnDefinition);
            },
            null,
            \YeAPF\ORM\DocumentModel::class
        );

        $constraint = $converter($documentModel, $fixture);

        $this->assertSame('amount', $constraint['keyName']);
        $this->assertSame(YeAPF_TYPE_FLOAT, $constraint['keyType']);
        $this->assertSame(10, $constraint['decimals']);
        $this->assertSame(2, $constraint['length']);
        $this->assertTrue($constraint['required']);
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
