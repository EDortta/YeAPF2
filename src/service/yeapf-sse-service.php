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

    static public function __startup()
    {
        self::$queue = new OpenSwoole\Table(1000);
        self::$queue->column('target', OpenSwoole\Table::TYPE_STRING, 6);
        self::$queue->column('lastHeartbeat', OpenSwoole\Table::TYPE_INT, 8);
        self::$queue->column('queue', OpenSwoole\Table::TYPE_STRING, 64 * 1024);
        self::$queue->create();
    }

    static public function __shutdown()
    {
        self::$queue = null;
    }

    static public function registerTarget($target)
    {
        if (!self::$queue->exists($target)) {
            self::$queue->set($target, [
                'target' => $target,
                'lastHeartbeat' => 0,
                'queue' => '[]'
            ]);

            self::enqueueHeartBeat($target);
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
        if (self::$queue->exists($target)) {
            self::$queue->del($target);
        }
    }

    static public function enqueueEvent($target, $event, $data = null, $id = null, $retry = null)
    {
        /** I need to use SSEEvent in order to achieve better security level */
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        if ('*' == $target) {
            foreach (self::getRegisteredTargets() as $t) {
                self::enqueueEvent($t, $event, $data, $id, $retry);
            }
        } else {
            if (in_array($target, self::getRegisteredTargets())) {
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

    static private function enqueueHeartBeat($target)
    {
        if (in_array($target, self::getRegisteredTargets())) {
            $row = self::$queue->get($target);
            $lastHeartbeat = $row['lastHeartbeat'] ?? 0;
            $now = time();
            if ($now - $lastHeartbeat > 10) {
                echo "*** Heartbeat ($now - $lastHeartbeat = " . ($now - $lastHeartbeat) . ") on $target\n";
                $row['lastHeartbeat'] = $now;
                self::$queue->set($target, $row);

                self::enqueueEvent(
                    $target,
                    'heartbeat', [
                        'time' => $now
                    ],
                    null,
                    null
                );
            }
        }
    }

    static public function dequeueEvent($target)
    {
        $event = null;
        if (in_array($target, self::getRegisteredTargets())) {
            $row = self::$queue->get($target);
            if ($row) {
                $queue = json_decode($row['queue'], true);
                $event = array_shift($queue);
                $row['queue'] = json_encode($queue);
                self::$queue->set($target, $row);

                if (!$event) {
                    self::enqueueHeartBeat($target);
                }
            }
        }
        return $event;
    }
}

SSEUniqueQueue::__startup();

class TaggedServer extends Server
{
    private $clientId = null;
    private $running = false;

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
        $this->config = $this->getSection('sse') || json_decode("{'port':5200, 'host':'0.0.0.0'}");
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
            echo "Setting server running to ".($newValue ? "true" : "false")."\n";
            $this->__serverRunning = $newValue;
        }
    }

    public function getHost()
    {
        return $this->config->host;
    }

    public function getPort()
    {
        return $this->config->port;
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

    public function start($callback, $port = 5200, $host = '0.0.0.0')
    {
        $this->server = new TaggedServer(
            $host,
            $port,
            SWOOLE_PROCESS,
            SWOOLE_SOCK_TCP
        );

        $this->setServerRunning(true);        

        $user_callback = $callback;

        $worker_num = OpenSwoole\Util::getCPUNum() * 2;
        $reactor_num = OpenSwoole\Util::getCPUNum() * 2;

        $this->server->set([
            'open_http2_protocol' => true,
            'enable_coroutine' => true,
            'max_coroutine' => $worker_num * 5000,
            'reactor_num' => $reactor_num,
            'worker_num' => $worker_num,
            'max_request' => 100000,
            'buffer_output_size' => 4 * 1024 * 1024,  // 4MB
            'log_level' => SWOOLE_LOG_WARNING,
        ]);

        $this->server->on('Start', function (Server $server) use ($host, $port) {
            _log("Starting SSE service on $host:$port");
        });

        $this->server->on('Connect', function (Server $server, int $fd, int $reactorId) {
            if ($this->getServerRunning()) {
                echo ">>> New client connection [ $fd ]\n";
            } else {
                $server->close($fd);
            }
        });

        $this->server->on('Disconnect', function (Server $server, int $fd, int $reactorId) {
            echo "<<< Client connection closed [ $fd ]\n";
            $server->setRunning(false);
            unset($this->runningServers[$fd]);
        });

        $this->server->on('Request', function (Request $request, Response $response) use ($user_callback) {
            if ($this->getServerRunning()) {
                $server = $this->server;

                $response->header('Access-Control-Allow-Origin', '*');
                $response->header('Content-Type', 'text/event-stream');
                $response->header('Cache-Control', 'no-cache');
                $response->header('Connection', 'keep-alive');
                $response->header('X-Accel-Buffering', 'no');

                $clientId = $request->get['cid'] ?? $this->getNewClientId();

                $server->setClientId($clientId);
                $this->runningServers[$clientId] = $server;

                $server->setRunning(true);

                echo "New client request [ $clientId ]\n";

                go(function () use ($response, $user_callback, $clientId, $server) {
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
                            \Co::sleep(1);
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
            echo '<<< Closing SSE connection ' . $server->getClientId();
            $server->setRunning(false);
            $server->stop();
        });

        $this->server->start();
    }

    public function stop()
    {
        $this->setServerRunning(false);
        foreach ($this->runningServers as $server) {
            echo "Stopping client " . $server->getClientId() . "\n";
            $server->setRunning(false);
        }
        $this->server->shutdown();
        $this->server = null;
    }
}
