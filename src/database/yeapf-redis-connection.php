<?php declare(strict_types=1);

namespace YeAPF\Connection\DB;

class RedisConnection extends \YeAPF\Connection\DBConnection
{
    private static $config;
    private static $redis; 
    private static ?int $defaultExpirationTime = null;   

    private function connect()
    {
        $auxConfig = self::$config->redis ?? new \stdClass();

        _trace('Trying to connect to Redis Server');
        _trace(print_r($auxConfig,true));
        do {
            try {
                self::$redis = new \Redis();
                self::$redis->connect(
                    $auxConfig->server ?? '127.0.0.1',
                    $auxConfig->port ?? 6379
                );
                if (isset($auxConfig->password)) {
                    self::$redis->auth($auxConfig->password);
                }
                self::setConnected(true);
                _trace('Connected to Redis');
            } catch (\Exception $th) {
                self::setConnected(false);
                if ($auxConfig->halt_on_error ?? false) {
                    throw new \YeAPF\YeAPFException($th->getMessage(), YeAPF_REDIS_CONNECTION, $th);
                } else {
                    _trace('+----------------------');
                    _trace('| REDIS NOT AVAILABLE! ');
                    _trace('|   at '.($auxConfig->server ?? 'undefined'));
                    _trace('|    @ '.($auxConfig->port ?? 'undefined'));
                    _trace('| ' . $th->getMessage() . '');
                    _trace('+----------------------');
                    sleep(5);
                }
            }
        } while (!self::getConnected());
    }

    public function __construct()
    {
        self::$config = parent::__construct()->config ?? null;
        self::connect();
    }

    public function getConfig()
    {
        return self::$config;
    }

    public function defaultExpirationTime($expiration=null) {
        if ($expiration===null) {
            return self::$defaultExpirationTime??null;
        } else {
            self::$defaultExpirationTime = $expiration;
        }
    }

    public function type($name)
    {
        $ret = '';
        if (self::getConnected()) {
            $ret = self::$redis->type($name);
        }
        return $ret;
    }

    public function set(string $name, mixed $value)
    {
        if (self::getConnected()) {
            self::$redis->set($name, $value);
        }
    }

    public function get(string $name)
    {
        if (self::getConnected()) {
            return self::$redis->get($name);
        }
    }

    public function delete(string $name)
    {
        if (self::getConnected()) {
            self::$redis->del($name);
        }
    }

    public function keys(string $filter = '*')
    {
        if (self::getConnected()) {
            return self::$redis->keys($filter);
        }
    }

    /**
     * hashes
     */
    public function exists(string $name, string $field)
    {
        $ret = false;
        if (self::getConnected()) {
            $ret = self::$redis->exists($name);
        }
        return $ret;
    }

    public function hset(string $name, mixed $data, int $expiration = null)
    {
        $ret = false;
        if (self::getConnected()) {
            $ret = true;
            if (is_iterable($data)) {
                foreach ($data as $key => $value) {
                    if (!is_numeric($key)) {
                        $ctl = self::$redis->hset($name, $key, "$value");
                        if (false === $ctl) {
                            $ret = false;
                            break;
                        }
                    }
                }
            } else {
                throw new \YeAPF\YeAPFException("It's not an iterable data", YeAPF_INVALID_DATA);
            }
            // $ret = (true != $ctl);
            \_trace("HSET data: ".json_encode($data));
            \_trace("HSET  ret: $ret");
            if (empty($expiration)) {
                $expiration=self::defaultExpirationTime();
            }
            if ($expiration !== null && $expiration>0) {
                \_trace("HSET setting expiration: $expiration");
                self::$redis->expire($name, $expiration);
            }
        } else {
            \_trace("HSET    : Not connected");
        }
        return $ret;
    }

    public function hget(string $name, string $field)
    {
        $ret = false;
        if (self::getConnected()) {
            $ret = self::$redis->hget($name, $field);
        }
        return $ret;
    }

    public function hgetall(string $name)
    {
        $ret = false;
        if (self::getConnected()) {
            $ret = self::$redis->hgetall($name);
        }
        return $ret;
    }

    public function hlen(string $name)
    {
        $ret = 0;
        if (self::getConnected()) {
            $ret = self::$redis->hlen($name);
        }
        return $ret;
    }

    public function hdel(string $name, string $field)
    {
        $ret = false;
        if (self::getConnected()) {
            $ret = self::$redis->hdel($name, $field);
        }
        return $ret;
    }

    public function setKeyExpiration(string $name, int $expiration = null)
{
    if (self::getConnected()) {
        if ($expiration !== null) {
            self::$redis->expire($name, $expiration);
        }
    }
}

    public function setGlobalExpiration(int $expiration = null)
    {
        if (self::getConnected()) {
            if ($expiration !== null) {
                $keys = self::$redis->keys('*');
                foreach ($keys as $key) {
                    self::$redis->expire($key, $expiration);
                }
            }
            self::defaultExpirationTime($expiration);
        }
    }
}

function CreateMainRedisConnection()
{
    global $yeapfRedisConnection;

    if (!isset($yeapfRedisConnection)) {
        _trace('Creating Main Redis Connection');
        $yeapfRedisConnection = new RedisConnection();
    }
    return $yeapfRedisConnection;
}

function GetMainRedisConnection()
{
    global $yeapfRedisConnection;

    return $yeapfRedisConnection;
}
