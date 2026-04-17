<?php declare(strict_types=1);

namespace YeAPF\Connection\DB;

class PDOConnectionLock
{
    private static $lock = null;
    private static $PDOConnectionCounter = 0;
    private static $lockName = 'PDOConnectionLock';

    private static function startup()
    {
        self::$lock = new \YeAPF\yLock();
    }

    private static function sleep(float $seconds)
    {
        $start = microtime(true);

        while (microtime(true) - $start < $seconds) {
            \Swoole\Coroutine::yield();
        }
    }

    public static function getNewPoolId()
    {
        if (null == self::$lock)
            self::startup();
        return ++self::$PDOConnectionCounter;
    }

    public static function lock()
    {
        if (null == self::$lock)
            self::startup();

        do {
            $ret = self::$lock->lock(self::$lockName, false, 100);
            if (!$ret) {
                $sleep = mt_rand(1, 10) / 10;
                _trace("Sleeping $sleep seconds");
                self::sleep($sleep);
            }
        } while (false == $ret);
        return $ret;
    }

    public static function unlock()
    {
        if (null == self::$lock)
            self::startup();
        $ret = self::$lock->unlock(self::$lockName);
        return $ret;
    }
}

class PDOConnection extends \YeAPF\Connection\DBConnection
{
    private static $config;
    private static $db;
    private static $trulyConnected = false;
    private static $connectionString;
    private static $pool = [];
    private static $poolId = null;
    private static $mainConnection = null;
    private ?PostgreSQLSchemaInspector $postgreSQLSchemaInspector = null;

    private function buildConnectionString(\stdClass $auxConfig): string
    {
        $driver = $auxConfig->driver ?? 'pgsql';
        $server = $auxConfig->server ?? '127.0.0.1';
        $port = $auxConfig->port ?? 5432;
        $dbname = $auxConfig->dbname ?? '';

        return sprintf('%s:host=%s;port=%s;dbname=%s', $driver, $server, $port, $dbname);
    }

    private function connect()
    {
        $auxConfig = self::$config->pdo ?? new \stdClass();

        if (self::$trulyConnected) {
            self::$poolId = PDOConnectionLock::getNewPoolId();
            \_trace('CONNECTING TO DATABASE SERVER (PDO)');
            do {
                try {
                    self::$connectionString = $this->buildConnectionString($auxConfig);
                    \_trace("connectionString: '" . self::$connectionString . "'");
                    self::$db = new \PDO(self::$connectionString, $auxConfig->user ?? 'VoidUserName', $auxConfig->password ?? 'VoidPassword');
                    self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
                    self::setConnected(true);
                } catch (\Throwable $th) {
                    self::setConnected(false);
                    if ($auxConfig->halt_on_error ?? false) {
                        throw new \YeAPF\YeAPFException($th->getMessage(), YeAPF_PDO_CONNECTION, $th);
                    } else {
                        \_trace('+----------------------');
                        \_trace('| PDO NOT AVAILABLE! ');
                        \_trace('|   at ' . self::$connectionString);
                        \_trace('| ' . $th->getMessage() . '');
                        \_trace('+----------------------');
                    }
                }
            } while (!self::getConnected());
        } else {
            $minPool = max(1, min(20, $auxConfig->pool ?? 5));
            \_trace("Building PDO connection pool with $minPool item(s)");
            for ($i = 0; $i < $minPool; $i++) {
                self::$pool[] = new self(true);
            }
        }
    }

    public function __construct($trulyConnected = false)
    {
        self::$trulyConnected = $trulyConnected;
        self::$config = parent::__construct()->config ?? null;
        self::connect();
    }

    public function getPoolId()
    {
        return self::$poolId;
    }

    public function popConnection(&$ret)
    {
        // if (PDOConnectionLock::lock()) {
        //     try {
        //         ...
        //     } finally {
        //         PDOConnectionLock::unlock();
        //     }
        // }

        $ret = array_pop(self::$pool);
        if (null == $ret) {
            $ret = new self(true);
        }

        if ($ret instanceof self) {
            \_trace('Connection to use: #' . $ret->getPoolId() . '');
            \_trace('Remaining pool: ' . count(self::$pool) . '');
        } else {
            throw new \YeAPF\YeAPFException('Unable to get a valid connection from pool', YeAPF_EMPTY_POOL);
        }
    }

    public function pushConnection($connection)
    {
        // if (PDOConnectionLock::lock()) {
        //     try {
        //     } finally {
        //         PDOConnectionLock::unlock();
        //     }
        // }
        \_trace('Connection parked: ' . $connection->getPoolId() . '');
        self::$pool[] = $connection;
    }

    private static function filterParams($params)
    {
        $filteredParams = [];
        if (null !== $params) {
            foreach ($params as $key => $value) {
                if (!is_object($value)) {
                    if (is_array($value)) {
                        $value = self::filterParams($value);
                    }
                    $filteredParams[$key] = $value;
                }
            }
        }
        return $filteredParams;
    }

    public function query($sql, $params = null)
    {
        $ret = null;
        if (self::getConnected()) {
            $sql = trim($sql);

            $fParams = self::filterParams($params);

            \_trace("SQL: $sql");
            \_trace('PARAMS: ' . json_encode($fParams));

            $ret = self::$db->prepare($sql);
            $ret->execute($fParams);
            $errorInfo = $ret->errorInfo();
            if ('00000' !== $errorInfo[0]) {
                \_trace('RET Error Info:');
                \_trace(print_r($ret->errorInfo(), true));

                $msg = str_replace("\n", ' ', $sql);
                $msg = preg_replace('/\s+/', ' ', $msg);

                throw new \YeAPF\YeAPFException('PGSQL-' . $errorInfo[0] . ': ' . $errorInfo[2] . " when doing:\n           " . $msg, $errorInfo[1]);
            } else {
                _trace('RowCount: ' . $ret->rowCount());
            }
        }
        return $ret;
    }

    public function queryAndFetch($sql, $data = null)
    {
        $ret = false;
        if (self::getConnected()) {
            $sql = trim($sql);
            $cmd = explode(' ', $sql)[0] ?? '';

            $stmt = self::query($sql, $data);

            if ($stmt) {
                // \_trace("stmt:");
                // print_r($stmt);

                if (strcasecmp($cmd, 'SELECT') == 0) {
                    $ret = $stmt->fetch();

                    // \_trace("after fetch");
                    // print_r($ret);
                }
            }
        }
        return $ret;
    }

    public function tableExists($tablename, $schemaname = null)
    {
        return $this->getPostgreSQLSchemaInspector()->tableExists((string) $tablename, $schemaname);
    }

    public function columnDefinition($tablename, $columnname, $schemaname = null)
    {
        return $this->getPostgreSQLSchemaInspector()->columnDefinition((string) $tablename, (string) $columnname, $schemaname);
    }

    public function columnExists($tablename, $columnname, $schemaname = null)
    {
        return $this->getPostgreSQLSchemaInspector()->columnExists((string) $tablename, (string) $columnname, $schemaname);
    }

    public function columns($tablename, $schemaname = null)
    {
        return $this->getPostgreSQLSchemaInspector()->columns((string) $tablename, $schemaname);
    }

    private function getConfiguredDriver(): string
    {
        $configDriver = self::$config->pdo->driver ?? 'pgsql';
        return strtolower((string) $configDriver);
    }

    private function getPostgreSQLSchemaInspector(): PostgreSQLSchemaInspector
    {
        if ('pgsql' !== $this->getConfiguredDriver()) {
            throw new \YeAPF\YeAPFException(
                'Schema inspection is currently available only for PostgreSQL in PDOConnection',
                YeAPF_UNIMPLEMENTED_KEY_TYPE
            );
        }

        if (null === $this->postgreSQLSchemaInspector) {
            $defaultSchema = self::$config->pdo->schema ?? 'public';
            $this->postgreSQLSchemaInspector = new PostgreSQLSchemaInspector($this, (string) $defaultSchema);
        }

        return $this->postgreSQLSchemaInspector;
    }

    public static function createMainConnection()
    {
        if (null == self::$mainConnection) {
            _trace('Creating new PDO main connection');
            self::$mainConnection = new self();
        }

        return self::$mainConnection;
    }

    public static function getMainConnection()
    {
        return self::$mainConnection;
    }
}

function CreateMainPDOConnection()
{
    return PDOConnection::createMainConnection();
}

function GetMainPDOConnection()
{
    return PDOConnection::getMainConnection();
}
