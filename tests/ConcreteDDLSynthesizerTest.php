<?php

declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use YeAPF\Connection\DB\Driver\DDLSynthesizerInterface;
use YeAPF\Connection\DB\Driver\MySQLDDLSynthesizer;
use YeAPF\Connection\DB\Driver\PostgreSQLDDLSynthesizer;

#[Group('contract')]
final class ConcreteDDLSynthesizerTest extends TestCase
{
    public function testBothEnginesProduceStableIdempotentPlans(): void
    {
        foreach ([new PostgreSQLDDLSynthesizer(), new MySQLDDLSynthesizer()] as $synthesizer) {
            $manifest = ['operations' => [[
                'type' => DDLSynthesizerInterface::OP_CREATE_TABLE,
                'schema' => $synthesizer instanceof PostgreSQLDDLSynthesizer ? 'public' : 'yeapf2_test',
                'table' => 'yeapf2_contract_types',
                'if_not_exists' => true,
                'columns' => [
                    ['name' => 'id', 'type' => 'integer', 'primary' => true],
                    ['name' => 'display name', 'type' => 'string', 'length' => 80],
                    ['name' => 'active', 'type' => 'boolean', 'default' => true],
                    ['name' => 'payload', 'type' => 'json', 'nullable' => true],
                ],
            ]]];

            $first = $synthesizer->synthesize($manifest);
            $second = $synthesizer->synthesize($manifest);

            $this->assertTrue($synthesizer->isValidPlan($first));
            $this->assertTrue($first['idempotent']);
            $this->assertSame($first['fingerprint'], $second['fingerprint']);
            $this->assertStringContainsString('IF NOT EXISTS', $first['statements'][0]['sql']);
            $this->assertStringContainsString('display name', $first['statements'][0]['sql']);
        }
    }

    public function testCanonicalOperationsCoverEnumForeignKeyIndexAndAlter(): void
    {
        $synthesizer = new PostgreSQLDDLSynthesizer();
        $plan = $synthesizer->synthesize(['operations' => [
            ['type' => 'create_enum', 'name' => 'yeapf2_status', 'enum_values' => ['new', 'done']],
            [
                'type' => 'alter_table', 'table' => 'yeapf2_child',
                'changes' => [['action' => 'add_column', 'name' => 'parent_id', 'type' => 'integer']],
            ],
            [
                'type' => 'add_foreign_key', 'table' => 'yeapf2_child',
                'foreign_key' => [
                    'name' => 'fk_parent', 'columns' => ['parent_id'],
                    'reference_table' => 'yeapf2_parent', 'reference_columns' => ['id'],
                ],
            ],
            [
                'type' => 'create_index', 'table' => 'yeapf2_child',
                'index' => ['name' => 'idx_parent', 'columns' => ['parent_id']],
            ],
        ]]);

        $this->assertSame(
            ['create_enum', 'alter_table', 'add_foreign_key', 'create_index'],
            array_column($plan['statements'], 'kind')
        );
        $this->assertStringContainsString('duplicate_object', $plan['statements'][0]['sql']);
        $this->assertStringContainsString('FOREIGN KEY', $plan['statements'][2]['sql']);
        $this->assertStringContainsString('CREATE INDEX IF NOT EXISTS', $plan['statements'][3]['sql']);
    }

    public function testInvalidTypeAndIdentifierAreRejectedBeforeExecution(): void
    {
        $synthesizer = new MySQLDDLSynthesizer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported canonical column type');
        $synthesizer->synthesize(['operations' => [[
            'type' => 'create_table', 'table' => 'yeapf2_invalid',
            'columns' => [['name' => 'bad', 'type' => 'executable-sql']],
        ]]]);
    }
}
