<?php declare(strict_types=1);

namespace YeAPF\Services;

abstract class Skeleton
{
    private $context = null;

    abstract function startup();
    abstract function shutdown();
    abstract public function start($port = 9999, $host = '0.0.0.0');
    abstract function configureAndStartup();

    public function openContext()
    {
        if (is_null($this->context)) {
            $this->context = new \YeAPF\Connection\PersistenceContext(
                new \YeAPF\Connection\DB\RedisConnection(),
                new \YeAPF\Connection\DB\PDOConnection()
            );
        }
    }

    public function getContext()
    {
        return $this->context;
    }

    public function closeContext()
    {
        $this->shutdown();
        $this->context = null;
    }
}
