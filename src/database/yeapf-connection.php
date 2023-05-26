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


        // if (file_exists(\YeAPF\YeAPFConfig::getConfigFile())) {

        //     $result->configFile = \YeAPF\YeAPFConfig::getConfigFile();

        //     $config = file_get_contents($result->configFile);
        //     if ($config !== false) {
        //         $result->config = json_decode($config, false);
        //     } else {
        //         throw new \Exception("Config file \YeAPF\YeAPFConfig::getConfigFile() cannot be readed", 1);
        //     }
        // } else {
        //     throw new \Exception("Config file \YeAPF\YeAPFConfig::getConfigFile() not found", 1);
        // }


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

