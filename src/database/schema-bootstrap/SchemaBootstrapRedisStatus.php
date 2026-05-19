<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

/**
 * Redis status for schema bootstrap. Works with real Redis or test doubles exposing get/set/del.
 */
final class SchemaBootstrapRedisStatus
{
    private const TTL_SECONDS = 3600;

    public function __construct(
        private readonly SchemaBootstrapPaths $paths,
        private readonly ?object $redis
    ) {
    }

    public function read(): ?array
    {
        if ($this->redis === null) {
            return null;
        }

        $raw = $this->redisGet($this->paths->redisStatusKey());
        if ($raw === false || $raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function write(string $state, array $extra = []): void
    {
        if ($this->redis === null) {
            return;
        }

        $payload = array_merge([
            'state' => $state,
            'startedAt' => gmdate('c'),
            'pid' => getmypid(),
            'host' => gethostname() ?: 'unknown',
        ], $extra);

        $this->redisSet(
            $this->paths->redisStatusKey(),
            json_encode($payload, JSON_THROW_ON_ERROR),
            self::TTL_SECONDS
        );
    }

    public function clear(): void
    {
        if ($this->redis === null) {
            return;
        }

        $this->redisDel($this->paths->redisStatusKey());
    }

    public function countActiveWorkers(): int
    {
        if ($this->redis === null) {
            return 0;
        }

        if (method_exists($this->redis, 'scard')) {
            $count = $this->redis->scard($this->paths->redisWorkersKey());
            return is_int($count) ? $count : 0;
        }

        if (method_exists($this->redis, 'hgetall')) {
            $all = $this->redis->hgetall($this->paths->redisWorkersKey());
            return is_array($all) ? count($all) : 0;
        }

        return 0;
    }

    public function registerWorkerHeartbeat(string $workerId): void
    {
        if ($this->redis === null) {
            return;
        }

        if (method_exists($this->redis, 'sadd')) {
            $this->redis->sadd($this->paths->redisWorkersKey(), $workerId);
            if (method_exists($this->redis, 'expire')) {
                $this->redis->expire($this->paths->redisWorkersKey(), 120);
            }
            return;
        }

        if (method_exists($this->redis, 'hset')) {
            $this->redis->hset($this->paths->redisWorkersKey(), [$workerId => (string) time()]);
        }
    }

    private function redisGet(string $key): mixed
    {
        if (method_exists($this->redis, 'get')) {
            return $this->redis->get($key);
        }

        return false;
    }

    private function redisSet(string $key, string $value, int $ttl): void
    {
        if (method_exists($this->redis, 'setex')) {
            $this->redis->setex($key, $ttl, $value);
            return;
        }

        if (method_exists($this->redis, 'set')) {
            $this->redis->set($key, $value);
        }
    }

    private function redisDel(string $key): void
    {
        if (method_exists($this->redis, 'del')) {
            $this->redis->del($key);
            return;
        }

        if (method_exists($this->redis, 'delete')) {
            $this->redis->delete($key);
        }
    }
}
