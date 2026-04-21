<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class PDOConnectionDriverAdapterTest extends TestCase
{
    public function testPostgreSqlAdapterExposesCapabilitiesAndExecutionMethods(): void
    {
        $connection = $this->getMockBuilder(\YeAPF\Connection\DB\PDOConnection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['query', 'queryAndFetch', 'queryAll', 'beginTransaction', 'commitTransaction', 'rollBackTransaction', 'getServerVersion'])
            ->getMock();

        $statement = $this->createMock(\PDOStatement::class);
        $statement->method('rowCount')->willReturn(3);

        $connection->expects($this->once())->method('query')->with('update x set y=:y', ['y' => 1])->willReturn($statement);
        $connection->expects($this->once())->method('queryAndFetch')->with('select * from x where id=:id', ['id' => 10])->willReturn(['id' => 10]);
        $connection->expects($this->once())->method('queryAll')->with('select * from x', [])->willReturn([['id' => 1], ['id' => 2]]);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commitTransaction');
        $connection->expects($this->once())->method('rollBackTransaction');
        $connection->expects($this->once())->method('getServerVersion')->willReturn('16.3');

        $schemaInspector = new FixtureSchemaInspector();
        $adapter = new \YeAPF\Connection\DB\PostgreSQLPDOAdapter($connection, $schemaInspector);

        $this->assertSame('pgsql', $adapter->getDriverName());
        $this->assertSame('16.3', $adapter->getDriverVersion());
        $this->assertSame(3, $adapter->execute('update x set y=:y', ['y' => 1]));
        $this->assertSame(['id' => 10], $adapter->fetchOne('select * from x where id=:id', ['id' => 10]));
        $this->assertSame([['id' => 1], ['id' => 2]], $adapter->fetchAll('select * from x'));

        $adapter->beginTransaction();
        $adapter->commit();
        $adapter->rollBack();

        $capabilities = $adapter->getCapabilities();
        $this->assertTrue($capabilities->isEnabled('schema_inspection'));
        $this->assertTrue($capabilities->isEnabled('transactions'));

        $normalized = $adapter->normalizeError(new RuntimeException('23505 duplicate key', 123), 'insert into x values (:id)', ['id' => 1]);
        $this->assertSame('pgsql', $normalized['driver']);
        $this->assertSame('23505', $normalized['sql_state']);
        $this->assertSame(123, $normalized['driver_code']);

        $this->assertSame($schemaInspector, $adapter->getSchemaInspector());
    }

    public function testPdoConnectionDelegatesSchemaMethodsToResolvedInspector(): void
    {
        $connectionReflection = new ReflectionClass(\YeAPF\Connection\DB\PDOConnection::class);
        /** @var \YeAPF\Connection\DB\PDOConnection $connection */
        $connection = $connectionReflection->newInstanceWithoutConstructor();

        $schemaInspector = new FixtureSchemaInspector();

        $schemaInspectorProperty = $connectionReflection->getProperty('schemaInspector');
        $schemaInspectorProperty->setAccessible(true);
        $schemaInspectorProperty->setValue($connection, $schemaInspector);

        $this->assertTrue($connection->tableExists('customers', 'public'));
        $this->assertTrue($connection->columnExists('customers', 'id', 'public'));
        $this->assertSame(['column_name' => 'id'], $connection->columnDefinition('customers', 'id', 'public'));
        $this->assertCount(1, $connection->columns('customers', 'public'));
    }
}

final class FixtureSchemaInspector implements \YeAPF\Connection\DB\Driver\SchemaInspectorInterface
{
    public function tableExists(string $tablename, ?string $schemaname = null): bool
    {
        return 'customers' === $tablename;
    }

    public function columnExists(string $tablename, string $columnname, ?string $schemaname = null): bool
    {
        return 'customers' === $tablename && 'id' === $columnname;
    }

    public function columnDefinition(string $tablename, string $columnname, ?string $schemaname = null): ?array
    {
        if ('customers' !== $tablename || 'id' !== $columnname) {
            return null;
        }

        return ['column_name' => 'id'];
    }

    public function columns(string $tablename, ?string $schemaname = null): array
    {
        if ('customers' !== $tablename) {
            return [];
        }

        return [[
            'column_name' => 'id',
            'column_default' => null,
            'is_nullable' => 'NO',
            'data_type' => 'integer',
            'character_maximum_length' => null,
            'numeric_precision' => 32,
            'numeric_scale' => null,
            'is_primary' => 1,
            'is_unique' => 1,
            'is_required' => 1,
        ]];
    }
}
