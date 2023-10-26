<?php
declare(strict_types=1);
namespace YeAPF\Connection;

// use Swoole\Coroutine;



class DBConnection
{
    private $connected;

    public function __construct() {

        $this->connected = false;

        $result =new \YeAPF\Result();

        $result->config = \YeAPF\YeAPFConfig::getSection("connection");

        return $result;
    }

    public function setConnected(bool $connected) {
        $this->connected = $connected;
    }

    public function getConnected() {
        return $this->connected;
    }

    public function sleep(float $seconds) {
        $start = microtime(true);

        while (microtime(true) - $start < $seconds) {
            \Swoole\Coroutine::yield();
        }
    }
}

