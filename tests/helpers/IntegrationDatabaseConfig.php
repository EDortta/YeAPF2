<?php

declare(strict_types=1);

namespace Tests\Helpers;

use InvalidArgumentException;

final class IntegrationDatabaseConfig
{
    /**
     * @return array{driver:string,host:string,port:int,dbname:string,schema:string,user:string,password:string}
     */
    public static function postgres(): array
    {
        return self::database('POSTGRES', [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 35432,
            'dbname' => 'yeapf2_test',
            'schema' => 'public',
            'user' => 'yeapf2',
            'password' => 'yeapf2',
        ]);
    }

    /**
     * @return array{driver:string,host:string,port:int,dbname:string,schema:string,user:string,password:string}
     */
    public static function mysql(): array
    {
        $config = self::database('MYSQL', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 33306,
            'dbname' => 'yeapf2_test',
            'schema' => 'yeapf2_test',
            'user' => 'yeapf2',
            'password' => 'yeapf2',
        ]);

        if (getenv('Y2_TEST_MYSQL_SCHEMA') === false) {
            $config['schema'] = $config['dbname'];
        }

        return $config;
    }

    /**
     * @param array{
     *   driver:string,host:string,port:int,dbname:string,schema:string,user:string,password:string
     * } $defaults
     * @return array{
     *   driver:string,host:string,port:int,dbname:string,schema:string,user:string,password:string
     * }
     */
    private static function database(string $engine, array $defaults): array
    {
        $prefix = 'Y2_TEST_' . $engine . '_';
        $portValue = self::value($prefix . 'PORT', (string) $defaults['port']);

        $validPort = filter_var(
            $portValue,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]]
        );
        if ($validPort === false) {
            throw new InvalidArgumentException(sprintf(
                '%sPORT must be an integer between 1 and 65535; received %s.',
                $prefix,
                var_export($portValue, true)
            ));
        }

        return [
            'driver' => $defaults['driver'],
            'host' => self::value($prefix . 'HOST', $defaults['host']),
            'port' => (int) $portValue,
            'dbname' => self::value($prefix . 'DATABASE', $defaults['dbname']),
            'schema' => self::value($prefix . 'SCHEMA', $defaults['schema']),
            'user' => self::value($prefix . 'USER', $defaults['user']),
            'password' => self::value($prefix . 'PASSWORD', $defaults['password'], allowEmpty: true),
        ];
    }

    private static function value(string $name, string $default, bool $allowEmpty = false): string
    {
        $value = getenv($name);
        if ($value === false) {
            return $default;
        }

        if (!$allowEmpty && trim($value) === '') {
            throw new InvalidArgumentException($name . ' cannot be empty.');
        }

        return $value;
    }
}
