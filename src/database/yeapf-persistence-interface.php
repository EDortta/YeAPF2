<?php
declare(strict_types=1);
namespace YeAPF\Connection;

class PersistenceContext
{
    static private $redisConnection=null;
    static private $pdoConnection=null;
    public function __construct(
        \YeAPF\Connection\DB\RedisConnection $redisConnection=null,
        \YeAPF\Connection\DB\PDOConnection $pdoConnection=null)
    {
        self::$redisConnection = $redisConnection;
        self::$pdoConnection = $pdoConnection;
    }

    public static function getRedisConnection(): \YeAPF\Connection\DB\RedisConnection {
        return self::$redisConnection;
    }

    public static function getPDOConnection(): \YeAPF\Connection\DB\PDOConnection {
        return self::$pdoConnection;
    }

}