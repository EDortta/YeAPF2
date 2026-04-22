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

        $this->assertSame(1, $driver->execute('insert into users(id,name) values (:id,:name)', ['id' => 1, 'name' => 'Ana']));

        $row = $driver->fetchOne('select * from users where id=:id', ['id' => 1]);
        $this->assertIsArray($row);
        $this->assertSame('Ana', $row['name'] ?? null);

        $all = $driver->fetchAll('select * from users', []);
        $this->assertNotEmpty($all);
        $this->assertSame(1, $all[0]['id'] ?? null);
    }

    public function testSchemaInspectorCanonicalMetadataContract(): void
    {
        $bootstrap = $this->createBootstrap();
        $inspector = $bootstrap->getSchemaInspector();

        $this->assertTrue($inspector->tableExists('users', 'public'));
        $this->assertTrue($inspector->columnExists('users', 'id', 'public'));

        $definition = $inspector->columnDefinition('users', 'id', 'public');
        $this->assertIsArray($definition);

        foreach (SchemaInspectorInterface::CANONICAL_COLUMN_METADATA_FIELDS as $field) {
            $this->assertArrayHasKey($field, $definition);
        }

        $columns = $inspector->columns('users', 'public');
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
                    'schema' => 'public',
                    'table' => 'users',
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
}
