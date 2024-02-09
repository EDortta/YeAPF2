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

    private function connect()
    {
        global $yAnalyzer;

        $auxConfig = self::$config->pdo ?? new \stdClass();

        if (self::$trulyConnected) {
            self::$poolId = PDOConnectionLock::getNewPoolId();
            \_trace('Trying to connect to Database Server (PDO)');
            do {
                try {
                    self::$connectionString = $yAnalyzer->do('#(driver):host=#(server);port=#(port);dbname=#(dbname)', json_decode(json_encode($auxConfig), true));
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
        if (null == $schemaname || '' == trim($schemaname)) {
            $schemaname = self::$config->pdo->schema;
        }

        $sql = 'select exists(select 1 from pg_tables where tablename=:tablename and schemaname=:schemaname)';
        $params = [
            'schemaname' => $schemaname,
            'tablename' => $tablename
        ];
        $ret = self::queryAndFetch($sql, $params);
        return (is_array($ret) && $ret['exists'] ?? false);
    }

    public function columnDefinition($tablename, $columnname, $schemaname = null)
    {
        if (null == $schemaname || '' == trim($schemaname)) {
            $schemaname = self::$config->pdo->schema;
        }
        $tablename = strtolower($tablename);
        $schemaname = strtolower($schemaname);
        $columnname = strtolower($columnname);

        $sql = 'select column_name, column_default, is_nullable, data_type, character_maximum_length, numeric_precision, numeric_scale from information_schema.columns where table_schema=:schemaname and table_name=:tablename and column_name=:columnname';
        $params = [
            'schemaname' => $schemaname,
            'tablename' => $tablename,
            'columnname' => $columnname
        ];
        $ret = self::queryAndFetch($sql, $params);
        return $ret;
    }

    public function columnExists($tablename, $columnname, $schemaname = null)
    {
        if (null == $schemaname || '' == trim($schemaname)) {
            $schemaname = self::$config->pdo->schema;
        }

        $tablename = strtolower($tablename);
        $schemaname = strtolower($schemaname);
        $columnname = strtolower($columnname);

        $sql = 'select column_name from information_schema.columns where table_schema=:schemaname and table_name=:tablename and column_name=:columnname';
        $params = [
            'schemaname' => $schemaname,
            'tablename' => $tablename,
            'columnname' => $columnname
        ];
        $ret = self::queryAndFetch($sql, $params);

        // \_trace("SQL: $sql");
        // \_trace("ret = ".json_encode($ret)."");

        if ($ret !== false) {
            $ret = strcasecmp($ret['column_name'] ?? '', $columnname) == 0;
            // \_trace("$tablename.$columnname Exists? ".($ret?"Yes":"No")."");
        }
        return $ret;
    }

    public function columns($tablename, $schemaname = null)
    {
        if (null == $schemaname || '' == trim($schemaname)) {
            $schemaname = self::$config->pdo->schema;
        }
        $tablename = strtolower($tablename);
        $schemaname = strtolower($schemaname);

        $sql = "SELECT c.column_name, c.column_default, c.is_nullable, c.data_type, c.character_maximum_length,
                        c.numeric_precision, c.numeric_scale,
                        CASE WHEN p.constraint_type = 'PRIMARY KEY' THEN 1 ELSE 0 END AS is_primary,
                        CASE WHEN u.constraint_name IS NOT NULL THEN 1 ELSE 0 END AS is_unique,
                        CASE WHEN c.is_nullable = 'NO' THEN 1 ELSE 0 END AS is_required
                    FROM information_schema.columns AS c
                    LEFT JOIN information_schema.key_column_usage AS k ON c.column_name = k.column_name
                        AND c.table_name = k.table_name AND c.table_schema = k.table_schema
                    LEFT JOIN information_schema.table_constraints AS p ON p.constraint_name = k.constraint_name
                        AND p.table_name = k.table_name AND p.table_schema = k.table_schema
                        AND p.constraint_type = 'PRIMARY KEY'
                    LEFT JOIN information_schema.table_constraints AS u ON u.constraint_name = k.constraint_name
                        AND u.table_name = k.table_name AND u.table_schema = k.table_schema
                        AND u.constraint_type = 'UNIQUE'
                    WHERE c.table_schema = :schemaname AND c.table_name = :tablename";
        $params = [
            'schemaname' => $schemaname,
            'tablename' => $tablename
        ];
        // echo "\n$sql\n";
        $ret = [];
        $q = self::query($sql, $params);
        // print_r($q);
        while ($d = $q->fetch()) {
            // print_r($d);
            $ret[] = $d;
        }
        // print_r($ret);
        return $ret;
    }
}

function CreateMainPDOConnection()
{
    global $yeapfMainPDOConnection;

    if (null == $yeapfMainPDOConnection) {
        _trace('Creating new PDO main connection');
        $yeapfMainPDOConnection = new PDOConnection();
    }
    return $yeapfMainPDOConnection;
}

function GetMainPDOConnection()
{
    global $yeapfMainPDOConnection;

    return $yeapfMainPDOConnection;
}
