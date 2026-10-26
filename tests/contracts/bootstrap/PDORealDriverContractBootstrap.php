<?php

declare(strict_types=1);

namespace Tests\Contracts\Bootstrap;

use ReflectionClass;
use ReflectionProperty;
use Tests\Contracts\DriverContractBootstrapInterface;
use YeAPF\Connection\DB\Driver\DBDriverInterface;
use YeAPF\Connection\DB\Driver\DDLSynthesizerInterface;
use YeAPF\Connection\DB\Driver\MySQLDDLSynthesizer;
use YeAPF\Connection\DB\Driver\PostgreSQLDDLSynthesizer;
use YeAPF\Connection\DB\Driver\SchemaInspectorInterface;
use YeAPF\Connection\DB\MySQLPDOAdapter;
use YeAPF\Connection\DB\MySQLSchemaInspector;
use YeAPF\Connection\DB\PDOConnection;
use YeAPF\Connection\DB\PostgreSQLPDOAdapter;
use YeAPF\Connection\DB\PostgreSQLSchemaInspector;
use YeAPF\YeAPFConfig;

abstract class PDORealDriverContractBootstrap implements DriverContractBootstrapInterface
{
    protected const TABLE = 'yeapf2_contract_users';
    protected const PLAN_TABLE = 'yeapf2_contract_plan';

    protected PDOConnection $connection;
    protected DBDriverInterface $driver;
    protected SchemaInspectorInterface $inspector;
    protected DDLSynthesizerInterface $synthesizer;

    /** @param array{driver:string,host:string,port:int,dbname:string,schema:string,user:string,password:string} $config */
    public function __construct(array $config)
    {
        self::resetConnectionStatics();
        (new ReflectionProperty(YeAPFConfig::class, 'configAreas'))->setValue(null, [
            'connection' => (object) ['pdo' => (object) [
                'driver' => $config['driver'],
                'server' => $config['host'],
                'port' => $config['port'],
                'dbname' => $config['dbname'],
                'schema' => $config['schema'],
                'user' => $config['user'],
                'password' => $config['password'],
                'halt_on_error' => true,
                'pool' => 1,
            ]],
            'mode' => (object) [
                'debug' => (object) ['enabled' => false, 'level' => 'WARNING', 'facility' => [], 'areas' => []],
                'trace' => (object) ['enabled' => false, 'level' => 'EMERG', 'areas' => []],
            ],
            'randomness' => (object) ['namespace' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'],
        ]);

        $this->connection = new PDOConnection(true);
        $this->configureImplementations($config['schema']);
        $this->resetFixtureTables();
    }

    abstract protected function configureImplementations(string $schema): void;

    public function getDriver(): DBDriverInterface
    {
        return $this->driver;
    }

    public function getSchemaInspector(): SchemaInspectorInterface
    {
        return $this->inspector;
    }

    public function getDDLSynthesizer(): DDLSynthesizerInterface
    {
        return $this->synthesizer;
    }

    protected function configurePostgreSQL(string $schema): void
    {
        $this->inspector = new PostgreSQLSchemaInspector($this->connection, $schema);
        $this->driver = new PostgreSQLPDOAdapter($this->connection, $this->inspector);
        $this->synthesizer = new PostgreSQLDDLSynthesizer();
    }

    protected function configureMySQL(string $schema): void
    {
        $this->inspector = new MySQLSchemaInspector($this->connection, $schema);
        $this->driver = new MySQLPDOAdapter($this->connection, $this->inspector);
        $this->synthesizer = new MySQLDDLSynthesizer();
    }

    private function resetFixtureTables(): void
    {
        $this->driver->execute('DROP TABLE IF EXISTS ' . $this->qualified(self::PLAN_TABLE));
        $this->driver->execute('DROP TABLE IF EXISTS ' . $this->qualified(self::TABLE));
        $plan = $this->synthesizer->synthesize(['operations' => [[
            'type' => DDLSynthesizerInterface::OP_CREATE_TABLE,
            'schema' => $this->getSchemaName(),
            'table' => self::TABLE,
            'if_not_exists' => true,
            'columns' => [
                ['name' => 'id', 'type' => 'integer', 'primary' => true],
                ['name' => 'name', 'type' => 'string', 'length' => 120],
            ],
        ]]]);
        foreach ($plan['statements'] as $statement) {
            $this->driver->execute($statement['sql']);
        }
    }

    private function qualified(string $table): string
    {
        $quote = $this->getEngineName() === 'mysql' ? '`' : '"';
        return $quote . $this->getSchemaName() . $quote . '.' . $quote . $table . $quote;
    }

    private static function resetConnectionStatics(): void
    {
        $reflection = new ReflectionClass(PDOConnection::class);
        foreach (
            [
            'config' => null, 'db' => null, 'trulyConnected' => false,
            'connectionString' => null, 'pool' => [], 'poolId' => null, 'mainConnection' => null,
            ] as $name => $value
        ) {
            $property = $reflection->getProperty($name);
            $property->setValue(null, $value);
        }
    }
}
