<?php declare(strict_types=1);

namespace YeAPF\Services;

use OpenSwoole\Coroutine\Channel;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;
use OpenSwoole\Constant;
use OpenSwoole\Coroutine;
use OpenSwoole;

class SSEEvent extends \YeAPF\SanitizedKeyData
{
    public $event = null;
    public $data = null;
    public $id = null;
    public $retry = null;

    public function __construct($event, $data = null, $id = null, $retry = null)
    {
        parent::__construct();
        $this->event = $event;
        $this->data = $data;
        $this->id = $id;
        $this->retry = $retry;
    }

    public function __toString()
    {
        return json_encode($this->exportData);
    }
}

class SSEUniqueQueue
{
    static protected $queue;
    static protected $config;
    static protected $lock;

    static public function __startup()
    {
        self::$config = new OpenSwoole\Table(20);
        self::$config->column('id', OpenSwoole\Table::TYPE_STRING, 16);
        self::$config->column('value', OpenSwoole\Table::TYPE_STRING, 256);
        self::$config->create();

        // self::setConfig('globalHeartbeat', 'Y');
        // self::setConfig('globalHeartbeatTimeout', '5');

        self::$queue = new OpenSwoole\Table(1000);
        self::$queue->column('target', OpenSwoole\Table::TYPE_STRING, 6);
        self::$queue->column('lastHeartbeat', OpenSwoole\Table::TYPE_INT, 8);
        self::$queue->column('queue', OpenSwoole\Table::TYPE_STRING, 64 * 1024);
        self::$queue->create();

        self::$lock = new OpenSwoole\Lock(SWOOLE_MUTEX);
    }

    static public function getConfig($key = null)
    {
        $ret = self::$config->get($key) ?? ['value' => null];
        if ($ret) {
            $ret = $ret['value'];
        }
        return $ret;
    }

    static public function setConfig(string $key, $value)
    {
        $row = self::$config->get($key) ?? ['value' => null];
        if (!is_array($row))
            $row = [];
        $row['value'] = $value;
        self::$config->set($key, $row);
    }

    static public function __shutdown()
    {
        self::$queue = null;
    }

    static public function registerTarget($target)
    {
        try {
            self::$lock->lock();
            if (!self::$queue->exists($target)) {
                echo "@ Registering target: $target\n";
                self::$queue->set($target, [
                    'target' => $target,
                    'lastHeartbeat' => 0,
                    'queue' => '[]'
                ]);
                self::enqueueHeartBeat($target, true);
            }
        } catch (\Exception $e) {
            echo 'Error registering target: ' . $e->getMessage() . "\n";
        } finally {
            self::$lock->unlock();
        }
    }

    static public function getRegisteredTargets()
    {
        $targets = [];
        foreach (self::$queue as $row) {
            $targets[] = $row['target'];
        }
        return $targets;
    }

    static public function unregisterTarget($target)
    {
        try {
            self::$lock->lock();
            if (self::$queue->exists($target)) {
                echo "@ Unregistering target: $target\n";
                self::$queue->del($target);
            }
        } finally {
            self::$lock->unlock();
        }
    }

    static public function enqueueEvent($target, $event, $data = null, $id = null, $retry = null)
    {
        $registeredTargets = self::getRegisteredTargets();
        /** I need to use SSEEvent in order to achieve better security level */
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        if ('*' == $target) {
            $cc = 1;
            foreach ($registeredTargets as $t) {
                echo "@ Enqueueing event to $t ($cc)\n";
                $cc++;
                self::enqueueEvent($t, $event, $data, $id, $retry);
            }
        } else {
            if (in_array($target, $registeredTargets)) {
                $payload = [
                    'event' => $event,
                    'data' => $data,
                    'id' => $id,
                    'retry' => $retry
                ];
                $payload = array_filter($payload);
                $row = self::$queue->get($target);
                if ($row === false) {
                    $row = [
                        'target' => $target,
                        'queue' => json_encode([$payload])
                    ];
                } else {
                    $queue = json_decode($row['queue'], true);
                    $queue[] = $payload;
                    $row['queue'] = json_encode($queue);
                }

                self::$queue->set($target, $row);
            }
        }
    }

    static private function enqueueHeartBeat($target, $force = false)
    {
        if (in_array($target, self::getRegisteredTargets())) {
            $row = self::$queue->get($target);
            $lastHeartbeat = $row['lastHeartbeat'] ?? 0;
            $now = time();
            $globalHeartbeat = self::getConfig('globalHeartbeat');
            $globalHeartbeatTimeout = self::getConfig('globalHeartbeatTimeout') ?? 23;
            if ($globalHeartbeat == 'Y') {
                $heartBeatOk = ($now % $globalHeartbeatTimeout) == 0 && ($now - $lastHeartbeat > $globalHeartbeatTimeout );
            } else {
                $heartBeatOk = ($now - $lastHeartbeat) > 10;
            }
            if ($heartBeatOk || $force) {
                $row['lastHeartbeat'] = $now;
                self::$queue->set($target, $row);

                self::enqueueEvent(
                    $target,
                    'heartbeat', [
                        'time' => $now,
                        'ghb' => $globalHeartbeat,
                        'ghbt' => $globalHeartbeatTimeout
                    ],
                    null,
                    null
                );
            }
        }
    }

    static public function dequeueEvent($target)
    {
        try {
            self::$lock->lock();
            $event = null;
            if (in_array($target, self::getRegisteredTargets())) {
                $row = self::$queue->get($target);
                $event = false;
                if ($row) {
                    $queue = json_decode($row['queue'], true);
                    $event = array_shift($queue);
                    $row['queue'] = json_encode($queue);
                    self::$queue->set($target, $row);
                }
                if (!$event) {
                    self::enqueueHeartBeat($target);
                }
            }
        } catch (\Exception $e) {
            echo 'ERROR DEQUEUEING EVENT: ' . $e->getMessage() . "\n";
        } finally {
            self::$lock->unlock();
        }
        return $event;
    }
}

SSEUniqueQueue::__startup();

class TaggedServer extends Server
{
    private $clientId = null;
    private $running = false;
    public int $fd = -1;    

    private function grantQueue()
    {
        SSEUniqueQueue::registerTarget($this->clientId);
    }

    private function revokeQueue()
    {
        SSEUniqueQueue::unregisterTarget($this->clientId);
    }

    public function setClientId($newId)
    {
        $this->clientId = $newId;
        $this->grantQueue();
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setRunning(bool $newValue)
    {
        if ($newValue && !$this->running) {
            $this->grantQueue();
        } else {
            if (!$newValue && $this->running) {
                $this->revokeQueue();
            }
        }
        $this->running = $newValue;
    }

    public function getRunning()
    {
        return $this->running;
    }

    public function getNewClientId()
    {
        try {
            $this->lock->lock();
            $ret = ++$this->clientId;
        } finally {
            $this->lock->unlock();
        }
        return $ret;
    }

    public function enqueueEvent(string $event, string|array|object|null $data, string $id = null, int $retry = null)
    {
        $this->grantQueue();
        SSEUniqueQueue::enqueueEvent($this->clientId, $event, $data, $id, $retry);
    }

    public function dequeueEvent()
    {
        $this->grantQueue();
        return SSEUniqueQueue::dequeueEvent($this->clientId);
    }
}

abstract class SSEService extends \YeAPF\YeAPFConfig
{
    private $config = [];
    private $runningServers = [];
    private $msgId = 0;
    private $clientId = 0;
    private $lock = null;
    private $server = null;
    private $__serverRunning = false;

    public function __construct()
    {
        $this->config = $this->getSection('sse');
        if (is_null($this->config))
            $this->config = new \stdClass();
        if (empty($this->config->port))
            $this->config->port = 5200;
        if (empty($this->config->host))
            $this->config->host = '0.0.0.0';
        if (empty($this->config->globalHeartbeat))
            $this->config->globalHeartbeat = 'Y';
        if (empty($this->config->globalHeartbeatTimeout))
            $this->config->globalHeartbeatTimeout = '5';

        echo 'SSEService: ' . $this->getHost() . ':' . $this->getPort() . "\n";
        echo '  globalHeartbeat: ' . $this->config->globalHeartbeat . "\n";
        echo '  globalHeartbeatTimeout: ' . $this->config->globalHeartbeatTimeout . "\n";

        SSEUniqueQueue::setConfig('globalHeartbeat', $this->config->globalHeartbeat);
        SSEUniqueQueue::setConfig('globalHeartbeatTimeout', $this->config->globalHeartbeatTimeout);

        $this->msgId = time();
        $this->lock = new OpenSwoole\Lock(SWOOLE_MUTEX);
    }

    public function getServerRunning()
    {
        return $this->__serverRunning;
    }

    public function setServerRunning(bool $newValue)
    {
        if ($newValue != $this->__serverRunning) {
            $this->__serverRunning = $newValue;
        }
    }

    public function getHost()
    {
        return (string) $this->config->host;
    }

    public function getPort()
    {
        return (int) $this->config->port;
    }

    private function registerServer(int $fd, $server)
    {
        $this->runningServers[$fd] = $server;
    }

    private function unregisterServer(int $fd)
    {
        unset($this->runningServers[$fd]);
    }

    public function addEvent(string $target, string $event, string|array|object|null $data, string $id = null)
    {
        try {
            $this->lock->lock();
            SSEUniqueQueue::enqueueEvent($target, $event, $data, $id);
        } finally {
            $this->lock->unlock();
        }
    }

    public function start($callback, $mqttProcessor)
    {
        $host = $this->getHost();
        $port = $this->getPort();
        $this->server = new TaggedServer(
            $host,
            $port,
            SWOOLE_PROCESS,
            SWOOLE_SOCK_TCP
        );

        $this->setServerRunning(true);

        $user_callback = $callback;

        $factor = 6;

        $worker_num = OpenSwoole\Util::getCPUNum() * $factor;
        $reactor_num = OpenSwoole\Util::getCPUNum() * $factor;

        $this->server->set([
            'open_http2_protocol' => true,
            'enable_coroutine' => true,
            'max_coroutine' => $worker_num * 5000,
            'reactor_num' => $reactor_num,
            'worker_num' => $worker_num,
            'max_request' => 100000,
            'buffer_output_size' => $factor * 1024 * 1024,  // 4MB
            'log_level' => SWOOLE_LOG_WARNING,
        ]);

        $this->server->on('Start', function (Server $server) use ($host, $port) {
            _log("Starting SSE service on $host:$port");
        });

        $this->server->on('Connect', function (Server $server, int $fd, int $reactorId) {
            if ($this->getServerRunning()) {
                echo ">>> New client connection [ $fd ]\n";
                $this->registerServer($fd, $server);
            } else {
                $server->close($fd);
            }
        });

        $this->server->on('Disconnect', function (Server $server, int $fd, int $reactorId) {
            echo "<<< Client connection closed [ $fd ]\n";
            $server->setRunning(false);
            $this->unregisterServer($fd);
        });

        $this->server->on('Request', function (Request $request, Response $response) use ($user_callback) {
            if ($this->getServerRunning()) {
                $fd = $request->fd;
                $cid = $request->get['cid'] ?? $this->getNewClientId();

                $server = $this->runningServers[$fd];
                $server->fd = $fd;

                echo 'Current clientID: ' . $server->getClientId() . "\n";

                $response->header('Access-Control-Allow-Origin', '*');
                $response->header('Content-Type', 'text/event-stream');
                $response->header('Cache-Control', 'no-cache');
                $response->header('Connection', 'keep-alive');
                $response->header('X-Accel-Buffering', 'no');

                $clientId = $cid;
                echo "New client request [ $clientId ]\n";

                $server->setClientId($clientId);

                $server->setRunning(true);

                go(function () use ($response, $user_callback, $clientId, $server) {
                    $counter = 0;
                    while ($server->getRunning()) {
                        if (is_callable($user_callback)) {
                            call_user_func($user_callback, $clientId);
                        }
                        $event = $server->dequeueEvent($clientId);
                        if ($event) {
                            echo "[ sending event: {$event['event']} {$event['data']} to $clientId ]\n";
                            $response->write("event: {$event['event']}\n");
                            if ($event['id'] ?? null) {
                                $response->write("id: {$event['id']}\n");
                            }
                            $response->write("data: {$event['data']}\n\n");
                        } else {
                            $counter++;
                            if ($counter % 4 == 0) {
                                \Co::wait(1);
                            } else {
                                \Co::sleep(1);
                            }
                        }
                    }

                    $response->close();
                    echo "Client request closed [ $clientId ]\n";
                    $server->stop();
                    unset($this->runningServers[$clientId]);
                    $server = null;
                });
            }
        });

        $this->server->on('Close', function (Server $server) {
            echo '<<< Closing SSE connection ' . $server->getClientId(). "\n";
            $server->setRunning(false);
            $this->unregisterServer($server->fd??-1);
            // $server->stop();
        });

        $user_mqtt_processor = $mqttProcessor;
        OpenSwoole\Timer::tick(1000, $user_mqtt_processor);        
        OpenSwoole\Timer::tick(15000, function () {
            $msg = [
                'time' => time(),
                'rnd' => rand(10000000, 99999999),
                'host' => gethostname(),
            ];
            $this->addEvent('*', 'heartbeat', json_encode($msg));
        });
        $this->server->start();
    }

    public function stop()
    {
        $this->setServerRunning(false);
        foreach ($this->runningServers as $server) {
            echo 'Stopping client ' . $server->getClientId() . "\n";
            $server->setRunning(false);
        }
        $this->server->shutdown();
        $this->server = null;
    }
}
