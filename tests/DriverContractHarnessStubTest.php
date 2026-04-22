<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';
require_once __DIR__ . '/contracts/DriverContractBootstrapInterface.php';
require_once __DIR__ . '/contracts/AbstractDriverContractTestCase.php';
require_once __DIR__ . '/contracts/bootstrap/StubDriverContractBootstrap.php';

use Tests\Contracts\AbstractDriverContractTestCase;
use Tests\Contracts\Bootstrap\StubDriverContractBootstrap;
use Tests\Contracts\DriverContractBootstrapInterface;

final class DriverContractHarnessStubTest extends AbstractDriverContractTestCase
{
    protected function createBootstrap(): DriverContractBootstrapInterface
    {
        return new StubDriverContractBootstrap();
    }
}
