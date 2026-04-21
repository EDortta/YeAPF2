<?php declare(strict_types=1);

final class InMemoryRedisConnection extends \YeAPF\Connection\DB\RedisConnection
{
    /** @var array<string,array<string,string>> */
    private array $hashes = [];

    public function __construct()
    {
    }

    public function getConnected()
    {
        return true;
    }

    public function exists(string $name, string $field = ''): bool
    {
        if ($field === '') {
            return isset($this->hashes[$name]);
        }

        return isset($this->hashes[$name][$field]);
    }

    public function type($name)
    {
        return isset($this->hashes[$name]) ? \Redis::REDIS_HASH : \Redis::REDIS_NOT_FOUND;
    }

    public function hset(string $name, mixed $data, int $expiration = null)
    {
        if (!is_iterable($data)) {
            return false;
        }

        $row = [];
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                continue;
            }
            $row[(string) $key] = (string) $value;
        }

        $this->hashes[$name] = $row;
        return true;
    }

    public function hget(string $name, string $field)
    {
        return $this->hashes[$name][$field] ?? false;
    }

    public function hgetall(string $name)
    {
        return $this->hashes[$name] ?? false;
    }

    public function hdel(string $name, string $field)
    {
        if (!isset($this->hashes[$name][$field])) {
            return false;
        }

        unset($this->hashes[$name][$field]);
        return true;
    }

    public function delete(string $name)
    {
        if (!isset($this->hashes[$name])) {
            return false;
        }

        unset($this->hashes[$name]);
        return true;
    }

    public function keys(string $filter = '*')
    {
        $regex = '/^' . str_replace('\\*', '.*', preg_quote($filter, '/')) . '$/';
        return array_values(
            array_filter(
                array_keys($this->hashes),
                static function (string $key) use ($regex): bool {
                    return preg_match($regex, $key) === 1;
                }
            )
        );
    }
}
