<?php

declare(strict_types=1);

namespace Tests\Contracts\Bootstrap;

final class PostgreSQLDriverContractBootstrap extends PDORealDriverContractBootstrap
{
    /** @param array{driver:string,host:string,port:int,dbname:string,schema:string,user:string,password:string} $config */
    public function __construct(private readonly array $config)
    {
        parent::__construct($config);
    }

    public function getEngineName(): string
    {
        return 'pgsql';
    }

    public function getSchemaName(): string
    {
        return $this->config['schema'];
    }

    protected function configureImplementations(string $schema): void
    {
        $this->configurePostgreSQL($schema);
    }
}
