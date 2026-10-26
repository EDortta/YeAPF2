<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Group;
use Tests\Contracts\AbstractDriverContractTestCase;
use Tests\Contracts\Bootstrap\MySQLDriverContractBootstrap;
use Tests\Contracts\DriverContractBootstrapInterface;
use Tests\Helpers\IntegrationDatabaseConfig;

#[Group('integration')]
#[Group('contract')]
#[Group('mysql')]
final class MySQLDriverContractTest extends AbstractDriverContractTestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = IntegrationDatabaseConfig::mysql();
        $socket = @fsockopen($config['host'], $config['port'], $errorCode, $errorMessage, 1.0);
        if ($socket === false) {
            self::markTestSkipped(
                'MySQL contract service is unavailable at ' . $config['host'] . ':' . $config['port']
                . ". Run 'docker compose --profile db up -d --wait mysql'."
            );
        }
        fclose($socket);
    }

    protected function createBootstrap(): DriverContractBootstrapInterface
    {
        return new MySQLDriverContractBootstrap(IntegrationDatabaseConfig::mysql());
    }
}
