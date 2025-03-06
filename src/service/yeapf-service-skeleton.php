<?php declare(strict_types=1);

namespace YeAPF\Services;

use YeAPF\Connection\PersistenceContext;
use YeAPF\Connection\DB\RedisConnection;
use YeAPF\Connection\DB\PDOConnection;
use YeAPF\yLogger;

/**
 * The base skeleton for any service implementation.
 * This class provides routing, security, lifecycle management, and database context.
 */
class ServiceSkeleton extends ServiceBase {
    private ?PersistenceContext $context = null;

    public function __construct() {
        parent::__construct();
        $this->openContext();
        if (method_exists($this, 'configureAndStartup')) {
            $this->configureAndStartup();
        }
    }

    public function openContext(): void {
        if (is_null($this->context)) {
            $this->context = new PersistenceContext(
                new RedisConnection(),
                new PDOConnection()
            );
        }
    }

    public function getContext(): ?PersistenceContext {
        return $this->context;
    }

    public function closeContext(): void {
        if (method_exists($this, 'shutdown')) {
            $this->shutdown();
        }
        $this->context = null;
    }

    public function start(?int $port = 9999, ?string $host = "0.0.0.0"): void {
        if (method_exists($this, 'startup')) {
            $this->startup();
        }
        yLogger::log(0, YeAPF_LOG_INFO, "Service started on $host:$port");
    }
}
