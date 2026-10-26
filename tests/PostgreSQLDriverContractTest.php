<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Group;
use Tests\Contracts\AbstractDriverContractTestCase;
use Tests\Contracts\Bootstrap\PostgreSQLDriverContractBootstrap;
use Tests\Contracts\DriverContractBootstrapInterface;
use Tests\Helpers\IntegrationDatabaseConfig;

#[Group('integration')]
#[Group('contract')]
#[Group('postgres')]
final class PostgreSQLDriverContractTest extends AbstractDriverContractTestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = IntegrationDatabaseConfig::postgres();
        $socket = @fsockopen($config['host'], $config['port'], $errorCode, $errorMessage, 1.0);
        if ($socket === false) {
            self::markTestSkipped(
                'PostgreSQL contract service is unavailable at ' . $config['host'] . ':' . $config['port']
                . ". Run 'docker compose --profile db up -d --wait postgres'."
            );
        }
        fclose($socket);
    }

    protected function createBootstrap(): DriverContractBootstrapInterface
    {
        return new PostgreSQLDriverContractBootstrap(IntegrationDatabaseConfig::postgres());
    }
}
