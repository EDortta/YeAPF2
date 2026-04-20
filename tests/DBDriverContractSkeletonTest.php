<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class DBDriverContractSkeletonTest extends TestCase
{
    public function testInterfacesAndCapabilitiesClassExist(): void
    {
        $this->assertTrue(interface_exists(\YeAPF\Connection\DB\Driver\DBDriverInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Connection\DB\Driver\SchemaInspectorInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Connection\DB\Driver\QueryDialectInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Connection\DB\Driver\DDLSynthesizerInterface::class));
        $this->assertTrue(class_exists(\YeAPF\Connection\DB\Driver\DriverCapabilities::class));
    }
}
