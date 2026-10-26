<?php

declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\IntegrationDatabaseConfig;

#[Group('unit')]
final class IntegrationDatabaseConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $previousEnvironment = [];

    protected function tearDown(): void
    {
        foreach ($this->previousEnvironment as $name => $value) {
            if ($value === false) {
                putenv($name);
            } else {
                putenv($name . '=' . $value);
            }
        }
        $this->previousEnvironment = [];
    }

    public function testDefaultsMatchRootInfrastructureContract(): void
    {
        $this->assertSame(35432, IntegrationDatabaseConfig::postgres()['port']);
        $this->assertSame(33306, IntegrationDatabaseConfig::mysql()['port']);
        $this->assertSame('yeapf2_test', IntegrationDatabaseConfig::postgres()['dbname']);
        $this->assertSame('yeapf2_test', IntegrationDatabaseConfig::mysql()['schema']);
    }

    public function testEnvironmentOverridesAreReadWithoutDotEnvLoading(): void
    {
        $this->setEnvironment('Y2_TEST_POSTGRES_HOST', 'postgres.internal');
        $this->setEnvironment('Y2_TEST_POSTGRES_PORT', '5433');
        $this->setEnvironment('Y2_TEST_POSTGRES_DATABASE', 'isolated_test');

        $config = IntegrationDatabaseConfig::postgres();

        $this->assertSame('postgres.internal', $config['host']);
        $this->assertSame(5433, $config['port']);
        $this->assertSame('isolated_test', $config['dbname']);
    }

    public function testInvalidPortFailsWithActionableVariableName(): void
    {
        $this->setEnvironment('Y2_TEST_MYSQL_PORT', 'not-a-port');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Y2_TEST_MYSQL_PORT must be an integer between 1 and 65535');

        IntegrationDatabaseConfig::mysql();
    }

    private function setEnvironment(string $name, string $value): void
    {
        if (!array_key_exists($name, $this->previousEnvironment)) {
            $this->previousEnvironment[$name] = getenv($name);
        }
        putenv($name . '=' . $value);
    }
}
