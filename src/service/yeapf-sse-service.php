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

    private static function log(string $message): void
    {
        if (function_exists('\_log')) {
            \_log($message);
        }
    }

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
                self::log("@ Registering target: $target");
                self::$queue->set($target, [
                    'target' => $target,
                    'lastHeartbeat' => 0,
                    'queue' => '[]'
                ]);
                self::enqueueHeartBeat($target, true);
            }
        } catch (\Exception $e) {
            self::log('Error registering target: ' . $e->getMessage());
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
                self::log("@ Unregistering target: $target");
                self::$queue->del($target);
            }
        } finally {
            self::$lock->unlock();
        }
    }

    static public function enqueueEvent($source, $target, $event, $data = null, $id = null, $retry = null)
    {
        $registeredTargets = self::getRegisteredTargets();
        /** I need to use SSEEvent in order to achieve better security level */
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        if ('*' == $target) {
            self::log("@ Broadcasting for all clients '$event' with data: $data");
            $cc = 1;
            foreach ($registeredTargets as $t) {
                self::log("@ Enqueueing event from '$source' to '$t' ($cc)");
                $cc++;
                self::enqueueEvent($source, $t, $event, $data, $id, $retry);
            }
        } else if (strpos($target, '*') !== false) {
            self::log("@ Broadcasting for some clients '$event' with data: $data");
            $cc = 1;
            foreach ($registeredTargets as $t) {
                if (fnmatch($target, $t)) {
                    self::log("@ Enqueueing event from '$source' to '$t' ($cc)");
                    $cc++;
                    self::enqueueEvent($source, $t, $event, $data, $id, $retry);
                }
            }
            if (0==$cc) {
                self::log("@ No target found for $target");
            }
        } else {
            if (in_array($target, $registeredTargets)) {
                $payload = [
                    'event' => $event,
                    'source' => $source,
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
                    'yeapf-sse-service',
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
            self::log('ERROR DEQUEUEING EVENT: ' . $e->getMessage());
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

    public function enqueueEvent(string $source, string $event, string|array|object|null $data, string $id = null, int $retry = null)
    {
        $this->grantQueue();
        SSEUniqueQueue::enqueueEvent($source, $this->clientId, $event, $data, $id, $retry);
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

    private function log(string $message): void
    {
        if (function_exists('\_log')) {
            \_log($message);
        }
    }

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

        $this->log('SSEService: ' . $this->getHost() . ':' . $this->getPort());
        $this->log('  globalHeartbeat: ' . $this->config->globalHeartbeat);
        $this->log('  globalHeartbeatTimeout: ' . $this->config->globalHeartbeatTimeout);

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

    public function addEvent(string $source, string $target, string $event, string|array|object|null $data, string $id = null)
    {
        try {
            $this->lock->lock();
            SSEUniqueQueue::enqueueEvent($source,$target, $event, $data, $id);
        } finally {
            $this->lock->unlock();
        }
    }

    public function start($callback, $mqttProcessor)
    {
        $host = $this->getHost();
        $port = $this->getPort();
        $this->server = $this->createTaggedServer($host, $port);
        $this->registerServerCallbacks($host, $port, $callback);
        $this->registerTimers($mqttProcessor);
        $this->server->start();
    }

    public function stop()
    {
        $this->setServerRunning(false);
        foreach ($this->runningServers as $server) {
            $this->log('Stopping client ' . $server->getClientId());
            $server->setRunning(false);
        }
        $this->server->shutdown();
        $this->server = null;
    }

    private function createTaggedServer(string $host, int $port): TaggedServer
    {
        $server = new TaggedServer(
            $host,
            $port,
            SWOOLE_PROCESS,
            SWOOLE_SOCK_TCP
        );

        $this->setServerRunning(true);

        $factor = 6;
        $worker_num = OpenSwoole\Util::getCPUNum() * $factor;
        $reactor_num = min(16, OpenSwoole\Util::getCPUNum() * $factor);

        $server->set([
            'open_http2_protocol' => true,
            'enable_coroutine' => true,
            'max_coroutine' => $worker_num * 5000,
            'reactor_num' => $reactor_num,
            'worker_num' => $worker_num,
            'max_request' => 100000,
            'buffer_output_size' => $factor * 1024 * 1024,
            'log_level' => SWOOLE_LOG_WARNING,
        ]);

        return $server;
    }

    private function registerServerCallbacks(string $host, int $port, $userCallback): void
    {
        $this->server->on('Start', fn(Server $server) => $this->onStart($host, $port));
        $this->server->on('Connect', fn(Server $server, int $fd, int $reactorId) => $this->onConnect($server, $fd));
        $this->server->on('Disconnect', fn(Server $server, int $fd, int $reactorId) => $this->onDisconnect($server, $fd));
        $this->server->on('Request', fn(Request $request, Response $response) => $this->handleRequest($request, $response, $userCallback));
        $this->server->on('Close', fn(Server $server) => $this->onClose($server));
    }

    private function registerTimers($mqttProcessor): void
    {
        OpenSwoole\Timer::tick(1000, $mqttProcessor);
        OpenSwoole\Timer::tick(15000, function () {
            $msg = [
                'time' => time(),
                'rnd' => rand(10000000, 99999999),
                'host' => gethostname(),
            ];
            $this->addEvent('yeapf-sse-service', '*', 'heartbeat', json_encode($msg));
        });
    }

    private function onStart(string $host, int $port): void
    {
        $this->log("Starting SSE service on $host:$port");
    }

    private function onConnect(Server $server, int $fd): void
    {
        if ($this->getServerRunning()) {
            $this->log(">>> New client connection [ $fd ]");
            $this->registerServer($fd, $server);
            return;
        }
        $server->close($fd);
    }

    private function onDisconnect(Server $server, int $fd): void
    {
        $this->log("<<< Client connection closed [ $fd ]");
        $server->setRunning(false);
        $this->unregisterServer($fd);
    }

    private function onClose(Server $server): void
    {
        $this->log('<<< Closing SSE connection ' . $server->getClientId());
        $server->setRunning(false);
        $this->unregisterServer($server->fd ?? -1);
    }

    private function handleRequest(Request $request, Response $response, $userCallback): void
    {
        if (!$this->getServerRunning()) {
            return;
        }

        $fd = $request->fd;
        $cid = $request->get['cid'] ?? $this->getNewClientId();
        /** @var TaggedServer $server */
        $server = $this->runningServers[$fd];
        $server->fd = $fd;

        $this->log('Current clientID: ' . $server->getClientId());

        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Cache-Control', 'no-cache');
        $response->header('Connection', 'keep-alive');
        $response->header('X-Accel-Buffering', 'no');

        $clientId = $cid;
        $this->log("New client request [ $clientId ]");
        $server->setClientId($clientId);
        $server->setRunning(true);

        go(function () use ($response, $userCallback, $clientId, $server) {
            $this->runClientEventLoop($response, (string) $clientId, $server, $userCallback);
        });
    }

    private function runClientEventLoop(Response $response, string $clientId, TaggedServer $server, $userCallback): void
    {
        $counter = 0;
        while ($server->getRunning()) {
            if (is_callable($userCallback)) {
                call_user_func($userCallback, $clientId);
            }
            $event = $server->dequeueEvent();
            if ($event) {
                $this->log("[ sending event: {$event['event']} {$event['data']} to $clientId ]");
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
        $this->log("Client request closed [ $clientId ]");
        $server->stop();
        unset($this->runningServers[$server->fd]);
    }
}
