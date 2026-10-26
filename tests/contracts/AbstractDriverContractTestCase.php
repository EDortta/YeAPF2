<?php declare(strict_types=1);

namespace Tests\Contracts;

use PHPUnit\Framework\TestCase;
use YeAPF\Connection\DB\Driver\SchemaInspectorInterface;

abstract class AbstractDriverContractTestCase extends TestCase
{
    abstract protected function createBootstrap(): DriverContractBootstrapInterface;

    public function testExecuteAndFetchContractSemantics(): void
    {
        $bootstrap = $this->createBootstrap();
        $driver = $bootstrap->getDriver();

        $this->assertSame(1, $driver->execute('insert into yeapf2_contract_users(id,name) values (:id,:name)', ['id' => 1, 'name' => 'Ana']));

        $row = $driver->fetchOne('select * from yeapf2_contract_users where id=:id', ['id' => 1]);
        $this->assertIsArray($row);
        $this->assertSame('Ana', $row['name'] ?? null);

        $all = $driver->fetchAll('select * from yeapf2_contract_users', []);
        $this->assertNotEmpty($all);
        $this->assertSame(1, $all[0]['id'] ?? null);
    }

    public function testSchemaInspectorCanonicalMetadataContract(): void
    {
        $bootstrap = $this->createBootstrap();
        $inspector = $bootstrap->getSchemaInspector();

        $this->assertTrue($inspector->tableExists('yeapf2_contract_users', $bootstrap->getSchemaName()));
        $this->assertTrue($inspector->columnExists('yeapf2_contract_users', 'id', $bootstrap->getSchemaName()));

        $definition = $inspector->columnDefinition('yeapf2_contract_users', 'id', $bootstrap->getSchemaName());
        $this->assertIsArray($definition);

        foreach (SchemaInspectorInterface::CANONICAL_COLUMN_METADATA_FIELDS as $field) {
            $this->assertArrayHasKey($field, $definition);
        }

        $columns = $inspector->columns('yeapf2_contract_users', $bootstrap->getSchemaName());
        $this->assertNotEmpty($columns);
        foreach (SchemaInspectorInterface::CANONICAL_COLUMN_METADATA_FIELDS as $field) {
            $this->assertArrayHasKey($field, $columns[0]);
        }
    }

    public function testDdlSynthesizerPlanContract(): void
    {
        $bootstrap = $this->createBootstrap();
        $synthesizer = $bootstrap->getDDLSynthesizer();

        $plan = $synthesizer->synthesize([
            'operations' => [
                [
                    'type' => 'create_table',
                    'schema' => $bootstrap->getSchemaName(),
                    'table' => 'yeapf2_contract_plan',
                    'columns' => [
                        ['name' => 'id', 'type' => 'integer', 'primary' => true],
                        ['name' => 'name', 'type' => 'string', 'length' => 120],
                    ],
                    'if_not_exists' => true,
                ],
            ],
            'metadata' => [
                'engine' => $bootstrap->getEngineName(),
            ],
        ]);

        $this->assertTrue($synthesizer->isValidPlan($plan));
        $this->assertSame($bootstrap->getEngineName(), $plan['metadata']['engine'] ?? null);
        $this->assertNotEmpty($plan['statements']);
    }
    public function testTransactionRollbackAndNormalizedErrors(): void
    {
        $bootstrap = $this->createBootstrap();
        $driver = $bootstrap->getDriver();
        $driver->beginTransaction();
        $driver->execute(
            'insert into yeapf2_contract_users(id,name) values (:id,:name)',
            ['id' => 99, 'name' => 'Rollback']
        );
        $driver->rollBack();

        $this->assertNull($driver->fetchOne('select * from yeapf2_contract_users where id=:id', ['id' => 99]));
        $normalized = $driver->normalizeError(new \RuntimeException('23000 fixture failure', 7));
        $this->assertSame($bootstrap->getEngineName(), $normalized['driver']);
        $this->assertSame('DB_ERROR', $normalized['normalized_code']);
    }

    public function testSynthesizedPlanCanBeAppliedTwiceWithoutMutation(): void
    {
        $bootstrap = $this->createBootstrap();
        $driver = $bootstrap->getDriver();
        $inspector = $bootstrap->getSchemaInspector();
        $plan = $bootstrap->getDDLSynthesizer()->synthesize(['operations' => [[
            'type' => 'create_table',
            'schema' => $bootstrap->getSchemaName(),
            'table' => 'yeapf2_contract_plan',
            'if_not_exists' => true,
            'columns' => [
                ['name' => 'id', 'type' => 'integer', 'primary' => true],
                ['name' => 'label', 'type' => 'string', 'length' => 80, 'nullable' => true],
            ],
        ]]]);

        foreach ($plan['statements'] as $statement) {
            $driver->execute($statement['sql']);
            $driver->execute($statement['sql']);
        }

        $this->assertTrue($inspector->tableExists('yeapf2_contract_plan', $bootstrap->getSchemaName()));
        $this->assertTrue($inspector->columnExists('yeapf2_contract_plan', 'label', $bootstrap->getSchemaName()));
    }

}
