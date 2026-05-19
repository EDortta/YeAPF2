<?php declare(strict_types=1);

/**
 * In-memory Redis double for schema bootstrap tests (string keys + sets).
 */
final class SchemaBootstrapTestRedis
{
    /** @var array<string,string> */
    private array $strings = [];

    /** @var array<string,array<string,bool>> */
    private array $sets = [];

    public function get(string $key): string|false
    {
        return $this->strings[$key] ?? false;
    }

    public function setex(string $key, int $ttl, string $value): bool
    {
        unset($ttl);
        $this->strings[$key] = $value;
        return true;
    }

    public function set(string $key, string $value): bool
    {
        $this->strings[$key] = $value;
        return true;
    }

    public function del(string $key): int
    {
        unset($this->strings[$key]);
        return 1;
    }

    public function sadd(string $key, string $member): int
    {
        $this->sets[$key][$member] = true;
        return 1;
    }

    public function scard(string $key): int
    {
        return isset($this->sets[$key]) ? count($this->sets[$key]) : 0;
    }

    public function expire(string $key, int $ttl): bool
    {
        unset($ttl);
        return isset($this->sets[$key]) || isset($this->strings[$key]);
    }
}
