<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

/**
 * Call at YeAPF worker boot so schema bootstrap can detect active instances.
 */
final class SchemaBootstrapWorkerRegistry
{
    public static function heartbeat(string $applicationRoot, ?object $redis, string $workerId): void
    {
        $paths = new SchemaBootstrapPaths($applicationRoot);
        $status = new SchemaBootstrapRedisStatus($paths, $redis);
        $status->registerWorkerHeartbeat($workerId);
    }
}
