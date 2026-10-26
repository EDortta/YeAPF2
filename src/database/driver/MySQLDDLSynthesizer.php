<?php

declare(strict_types=1);

namespace YeAPF\Connection\DB\Driver;

final class MySQLDDLSynthesizer extends SqlDDLSynthesizer
{
    protected function engine(): string
    {
        return 'mysql';
    }
}
