<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';
require_once __DIR__ . '/helpers/SchemaBootstrapTestRedis.php';
require_once __DIR__ . '/helpers/IntegrationDatabaseConfig.php';

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\IntegrationDatabaseConfig;

#[Group('integration')]
#[Group('postgres')]
final class SchemaSessionBootstrapTest extends TestCase
{
    private const TABLE = 'yeapf2_demo_users';

    private string $appRoot = '';

    /** @var array<string,mixed>|null */
    private static ?array $previousConfigAreas = null;

    protected function setUp(): void
    {
        if (!self::isDockerDbReachable()) {
            $this->markTestSkipped('PostgreSQL test service is not reachable at ' . self::endpoint() . ". Run 'docker compose --profile db up -d --wait postgres' from the repository root.");
        }

        $this->appRoot = sys_get_temp_dir() . '/y2-schema-bootstrap-' . bin2hex(random_bytes(4));
        mkdir($this->appRoot . '/.config', 0775, true);
        mkdir($this->appRoot . '/database/documentModels', 0775, true);
        copy(
            __DIR__ . '/fixtures/schema-bootstrap/documentModels/yeapf2_demo_users.json',
            $this->appRoot . '/database/documentModels/yeapf2_demo_users.json'
        );

        self::backupAndSetRuntimeConnectionConfig();
        self::resetPdoConnectionStatics();
        self::dropDemoTable();
        $this->resetBootstrapArtifacts();
    }

    protected function tearDown(): void
    {
        if (self::isDockerDbReachable()) {
            self::dropDemoTable();
            self::resetPdoConnectionStatics();
            self::restoreRuntimeConnectionConfig();
        }
        if ($this->appRoot !== '') {
            $this->removeDirectory($this->appRoot);
        }
    }

    public function testBootstrapCreatesTableAndIsIdempotent(): void
    {
        $redis = new SchemaBootstrapTestRedis();
        $context = new \YeAPF\Connection\PersistenceContext(null, null);
        $orchestrator = \YeAPF\SchemaBootstrap\SchemaSessionOrchestrator::factory($this->appRoot, $context, $redis);

        $catalogRuns = 0;
        $orchestrator->catalog()->register('count-run', static function () use (&$catalogRuns): void {
            $catalogRuns++;
        });

        $hash1 = $this->withPdo(static fn ($pdo) => $orchestrator->run(
            $context,
            $pdo,
            false
        ));

        $this->assertNotSame('', $hash1);
        $this->assertTrue(self::tableExists());
        $this->assertFileExists($this->appRoot . '/.config/schema-bootstrap/completed.marker');
        $this->assertFileDoesNotExist($this->appRoot . '/.config/schema-bootstrap/lock');
        $this->assertSame(1, $catalogRuns);

        $log = file_get_contents($this->appRoot . '/.config/schema-bootstrap/bootstrap.log');
        $this->assertIsString($log);
        $this->assertStringContainsString('phase=align', $log);
        $this->assertStringContainsString('phase=completed', $log);

        $hash2 = $this->withPdo(static fn ($pdo) => $orchestrator->run(
            $context,
            $pdo,
            false
        ));

        $this->assertSame($hash1, $hash2);
        $this->assertSame(2, $catalogRuns);
    }

    public function testGateBlocksWhenRedisShowsMigrating(): void
    {
        $redis = new SchemaBootstrapTestRedis();
        $paths = new \YeAPF\SchemaBootstrap\SchemaBootstrapPaths($this->appRoot);
        $redis->setex(
            $paths->redisStatusKey(),
            60,
            json_encode(['state' => \YeAPF\SchemaBootstrap\SchemaBootstrapState::MIGRATING], JSON_THROW_ON_ERROR)
        );

        $context = new \YeAPF\Connection\PersistenceContext(null, null);
        $orchestrator = \YeAPF\SchemaBootstrap\SchemaSessionOrchestrator::factory($this->appRoot, $context, $redis);

        $this->expectException(\YeAPF\SchemaBootstrap\SchemaBootstrappedException::class);
        $orchestrator->gate()->assertCanStart();
    }

    public function testBootstrapRefusesWhenWorkersActiveUnlessForce(): void
    {
        $redis = new SchemaBootstrapTestRedis();
        $paths = new \YeAPF\SchemaBootstrap\SchemaBootstrapPaths($this->appRoot);
        $redis->sadd($paths->redisWorkersKey(), 'worker-1');

        $context = new \YeAPF\Connection\PersistenceContext(null, null);
        $orchestrator = \YeAPF\SchemaBootstrap\SchemaSessionOrchestrator::factory($this->appRoot, $context, $redis);

        $this->expectException(\YeAPF\SchemaBootstrap\SchemaBootstrappedException::class);
        $this->withPdo(static fn ($pdo) => $orchestrator->run($context, $pdo, false));
    }

    public function testBootstrapForceAllowedWithWorkers(): void
    {
        $redis = new SchemaBootstrapTestRedis();
        $paths = new \YeAPF\SchemaBootstrap\SchemaBootstrapPaths($this->appRoot);
        $redis->sadd($paths->redisWorkersKey(), 'worker-1');

        $context = new \YeAPF\Connection\PersistenceContext(null, null);
        $orchestrator = \YeAPF\SchemaBootstrap\SchemaSessionOrchestrator::factory($this->appRoot, $context, $redis);

        $hash = $this->withPdo(static fn ($pdo) => $orchestrator->run($context, $pdo, true));
        $this->assertNotSame('', $hash);
        $log = file_get_contents($this->appRoot . '/.config/schema-bootstrap/bootstrap.log');
        $this->assertStringContainsString('force_with_active_workers', (string) $log);
    }

    private function resetBootstrapArtifacts(): void
    {
        $base = $this->appRoot . '/.config/schema-bootstrap';
        if (!is_dir($base)) {
            return;
        }

        foreach (glob($base . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function withPdo(callable $callback): mixed
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

    private static function tableExists(): bool
    {
        return (bool) self::withBorrowedConnection(static function ($pdo): bool {
            return $pdo->tableExists(self::TABLE);
        });
    }

    private static function dropDemoTable(): void
    {
        try {
            self::withBorrowedConnection(static function ($pdo): void {
                $pdo->query('DROP TABLE IF EXISTS "' . self::TABLE . '" CASCADE');
            });
        } catch (Throwable) {
        }
    }

    /** @return array{driver:string,host:string,port:int,dbname:string,schema:string,user:string,password:string} */
    private static function config(): array
    {
        return IntegrationDatabaseConfig::postgres();
    }

    private static function endpoint(): string
    {
        return self::config()['host'] . ':' . self::config()['port'];
    }

    private static function isDockerDbReachable(): bool
    {
        $socket = @fsockopen(self::config()['host'], self::config()['port'], $errno, $errstr, 1.0);
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

        $areasProperty->setValue(null, [
            'connection' => (object) [
                'pdo' => (object) [
                    'driver' => 'pgsql',
                    'server' => self::config()['host'],
                    'port' => self::config()['port'],
                    'dbname' => self::config()['dbname'],
                    'schema' => 'public',
                    'user' => self::config()['user'],
                    'password' => self::config()['password'],
                    'halt_on_error' => true,
                    'pool' => 1,
                ],
            ],
            'mode' => (object) [
                'debug' => (object) ['enabled' => false, 'level' => 'WARNING', 'facility' => [], 'areas' => []],
                'trace' => (object) ['enabled' => false, 'level' => 'EMERG', 'areas' => []],
            ],
            'randomness' => (object) ['namespace' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'],
        ]);
    }

    private static function restoreRuntimeConnectionConfig(): void
    {
        $configClass = new ReflectionClass(\YeAPF\YeAPFConfig::class);
        $areasProperty = $configClass->getProperty('configAreas');
        $areasProperty->setAccessible(true);
        $areasProperty->setValue(null, self::$previousConfigAreas ?? []);
    }

    private static function resetPdoConnectionStatics(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Connection\DB\PDOConnection::class);
        foreach (['config', 'db', 'trulyConnected', 'connectionString', 'pool', 'poolId', 'mainConnection'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, $propertyName === 'pool' ? [] : null);
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

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
