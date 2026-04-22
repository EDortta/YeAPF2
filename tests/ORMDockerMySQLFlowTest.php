<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';
require_once __DIR__ . '/helpers/InMemoryRedisConnection.php';

use PHPUnit\Framework\TestCase;

final class ORMDockerMySQLFlowTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 53306;
    private const DB = 'yeapf2_test';
    private const USER = 'yeapf2';
    private const PASSWORD = 'yeapf2';
    private const SCHEMA = 'yeapf2_test';
    private const TABLE = 'yeapf2_orm_flow_users_mysql';

    /** @var array<string,mixed>|null */
    private static ?array $previousConfigAreas = null;

    public static function setUpBeforeClass(): void
    {
        if (!self::isDockerDbReachable()) {
            self::markTestSkipped('MySQL docker container is not reachable at 127.0.0.1:53306. Start tests/db-environments first.');
        }

        self::backupAndSetRuntimeConnectionConfig();
        self::resetPdoConnectionStatics();
        self::dropTestTableIfExists();
    }

    public static function tearDownAfterClass(): void
    {
        self::dropTestTableIfExists();
        self::resetPdoConnectionStatics();
        self::restoreRuntimeConnectionConfig();
    }

    public function testOrmUsageStepByStepOnDockerMySQL(): void
    {
        $context = new \YeAPF\Connection\PersistenceContext(new InMemoryRedisConnection(), null);

        $model = new \YeAPF\ORM\DocumentModel($context, self::TABLE);
        $model->setConstraint('id', YeAPF_TYPE_STRING, length: 36, primary: true, required: true, protobufOrder: 1);
        $model->setConstraint('name', YeAPF_TYPE_STRING, length: 120, required: true, protobufOrder: 2);
        $model->setConstraint('email', YeAPF_TYPE_STRING, length: 180, acceptNULL: true, protobufOrder: 3);
        $model->setConstraint('hired', YeAPF_TYPE_BOOL, required: true, protobufOrder: 4);

        $collection = new \YeAPF\ORM\PersistentCollection($context, self::TABLE, 'id', $model);

        $this->assertTrue(self::withBorrowedConnection(static function ($pdo): bool {
            return $pdo->tableExists(self::TABLE, self::SCHEMA);
        }));

        $alice = clone $collection->getDocumentModel();
        $alice->id = 'user-001';
        $alice->name = 'Alice';
        $alice->email = 'alice@example.test';
        $alice->hired = true;
        $this->assertTrue($collection->setDocument('user-001', $alice));

        $stored = $collection->getDocument('user-001');
        $this->assertSame('Alice', $stored->name);
        $this->assertSame('alice@example.test', $stored->email);

        $aliceUpdated = clone $collection->getDocumentModel();
        $aliceUpdated->id = 'user-001';
        $aliceUpdated->name = 'Alice Updated';
        $aliceUpdated->email = 'alice.updated@example.test';
        $aliceUpdated->hired = true;
        $this->assertTrue($collection->setDocument('user-001', $aliceUpdated));

        $storedUpdated = $collection->getDocument('user-001');
        $this->assertSame('Alice Updated', $storedUpdated->name);

        $sample = clone $collection->getDocumentModel();
        $sample->name = 'Alice Updated';
        $found = $collection->findByExample($sample);
        $this->assertSame('user-001', $found->id);

        $ids = $collection->listDocuments();
        $this->assertContains('user-001', $ids);

        $collection->deleteDocument('user-001');
        $this->assertFalse($collection->hasDocument('user-001'));
    }

    private static function isDockerDbReachable(): bool
    {
        $socket = @fsockopen(self::HOST, self::PORT, $errno, $errstr, 1.0);
        if ($socket === false) {
            return false;
        }

        fclose($socket);
        return true;
    }

    private static function backupAndSetRuntimeConnectionConfig(): void
    {
        $configClass = new ReflectionClass(\YeAPF\YeAPFConfig::class);
        $areasProperty = $configClass->getProperty('configAreas');
        $areasProperty->setAccessible(true);

        $current = $areasProperty->getValue();
        self::$previousConfigAreas = is_array($current) ? $current : [];

        $areasProperty->setValue([
            'connection' => (object) [
                'pdo' => (object) [
                    'driver' => 'mysql',
                    'server' => self::HOST,
                    'port' => self::PORT,
                    'dbname' => self::DB,
                    'schema' => self::SCHEMA,
                    'user' => self::USER,
                    'password' => self::PASSWORD,
                    'halt_on_error' => true,
                    'pool' => 1,
                ],
            ],
            'mode' => (object) [
                'debug' => (object) [
                    'enabled' => false,
                    'level' => 'WARNING',
                    'facility' => [],
                    'areas' => [],
                ],
                'trace' => (object) [
                    'enabled' => false,
                    'level' => 'EMERG',
                    'areas' => [],
                ],
            ],
            'randomness' => (object) [
                'namespace' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
        ]);
    }

    private static function restoreRuntimeConnectionConfig(): void
    {
        $configClass = new ReflectionClass(\YeAPF\YeAPFConfig::class);
        $areasProperty = $configClass->getProperty('configAreas');
        $areasProperty->setAccessible(true);
        $areasProperty->setValue(self::$previousConfigAreas ?? []);
    }

    private static function resetPdoConnectionStatics(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Connection\DB\PDOConnection::class);
        $defaults = [
            'config' => null,
            'db' => null,
            'trulyConnected' => false,
            'connectionString' => null,
            'pool' => [],
            'poolId' => null,
            'mainConnection' => null,
        ];

        foreach ($defaults as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, $value);
        }
    }

    private static function dropTestTableIfExists(): void
    {
        try {
            self::withBorrowedConnection(static function ($pdo): void {
                $pdo->query('drop table if exists ' . self::TABLE);
            });
        } catch (Throwable $exception) {
        }
    }

    private static function withBorrowedConnection(callable $callback): mixed
    {
        $main = \YeAPF\Connection\DB\CreateMainPDOConnection();
        $conn = null;
        $main->popConnection($conn);
        try {
            return $callback($conn);
        } finally {
            $main->pushConnection($conn);
        }
    }
}
