<?php declare(strict_types=1);

namespace Tests\Contracts;

use YeAPF\Connection\DB\Driver\DBDriverInterface;
use YeAPF\Connection\DB\Driver\DDLSynthesizerInterface;
use YeAPF\Connection\DB\Driver\SchemaInspectorInterface;

interface DriverContractBootstrapInterface
{
    public function getEngineName(): string;

    public function getDriver(): DBDriverInterface;

    public function getSchemaInspector(): SchemaInspectorInterface;

    public function getDDLSynthesizer(): DDLSynthesizerInterface;
}
