<?php declare(strict_types=1);

namespace YeAPF\Connection\DB;

use YeAPF\Connection\DB\Driver\DBDriverInterface;
use YeAPF\Connection\DB\Driver\DriverCapabilities;
use YeAPF\Connection\DB\Driver\SchemaInspectorInterface;

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

final class PostgreSQLPDOAdapter implements DBDriverInterface
{
    private PDOConnection $connection;
    private SchemaInspectorInterface $schemaInspector;

    public function __construct(PDOConnection $connection, SchemaInspectorInterface $schemaInspector)
    {
        $this->connection = $connection;
        $this->schemaInspector = $schemaInspector;
    }

    public function getDriverName(): string
    {
        return 'pgsql';
    }

    public function getDriverVersion(): ?string
    {
        return $this->connection->getServerVersion();
    }

    public function getCapabilities(): DriverCapabilities
    {
        return new DriverCapabilities([
            'transactions' => true,
            'prepared_statements' => true,
            'schema_inspection' => true,
            'ddl_synthesis' => false,
            'json_type' => true,
            'enum_type' => true,
            'upsert' => true,
            'returning_clause' => true,
        ]);
    }

    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->connection->query($sql, $params);
        if (!$statement instanceof \PDOStatement) {
            return 0;
        }

        return (int) $statement->rowCount();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->connection->queryAndFetch($sql, $params);
        return is_array($row) ? $row : null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->connection->queryAll($sql, $params);
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commitTransaction();
    }

    public function rollBack(): void
    {
        $this->connection->rollBackTransaction();
    }

    public function normalizeError(\Throwable $throwable, ?string $sql = null, array $params = []): array
    {
        $message = trim((string) $throwable->getMessage());
        $sqlState = null;
        if (preg_match('/^([0-9A-Z]{5})\b/', $message, $matches) === 1) {
            $sqlState = $matches[1];
        }

        return [
            'driver' => $this->getDriverName(),
            'sql_state' => $sqlState,
            'driver_code' => $throwable->getCode(),
            'message' => '' !== $message ? $message : 'Database error',
            'normalized_code' => 'DB_ERROR',
            'is_transient' => false,
            'context' => [
                'sql' => $sql,
                'params' => $params,
            ],
        ];
    }

    public function getSchemaInspector(): SchemaInspectorInterface
    {
        return $this->schemaInspector;
    }
}

final class MySQLPDOAdapter implements DBDriverInterface
{
    private PDOConnection $connection;
    private SchemaInspectorInterface $schemaInspector;

    public function __construct(PDOConnection $connection, SchemaInspectorInterface $schemaInspector)
    {
        $this->connection = $connection;
        $this->schemaInspector = $schemaInspector;
    }

    public function getDriverName(): string
    {
        return 'mysql';
    }

    public function getDriverVersion(): ?string
    {
        return $this->connection->getServerVersion();
    }

    public function getCapabilities(): DriverCapabilities
    {
        return new DriverCapabilities([
            'transactions' => true,
            'prepared_statements' => true,
            'schema_inspection' => true,
            'ddl_synthesis' => false,
            'json_type' => true,
            'enum_type' => true,
            'upsert' => true,
            'returning_clause' => false,
        ]);
    }

    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->connection->query($sql, $params);
        if (!$statement instanceof \PDOStatement) {
            return 0;
        }

        return (int) $statement->rowCount();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->connection->queryAndFetch($sql, $params);
        return is_array($row) ? $row : null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->connection->queryAll($sql, $params);
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commitTransaction();
    }

    public function rollBack(): void
    {
        $this->connection->rollBackTransaction();
    }

    public function normalizeError(\Throwable $throwable, ?string $sql = null, array $params = []): array
    {
        $message = trim((string) $throwable->getMessage());
        $sqlState = null;
        if (preg_match('/^([0-9A-Z]{5})\b/', $message, $matches) === 1) {
            $sqlState = $matches[1];
        }

        return [
            'driver' => $this->getDriverName(),
            'sql_state' => $sqlState,
            'driver_code' => $throwable->getCode(),
            'message' => '' !== $message ? $message : 'Database error',
            'normalized_code' => 'DB_ERROR',
            'is_transient' => false,
            'context' => [
                'sql' => $sql,
                'params' => $params,
            ],
        ];
    }

    public function getSchemaInspector(): SchemaInspectorInterface
    {
        return $this->schemaInspector;
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
    private ?DBDriverInterface $driverAdapter = null;
    private ?SchemaInspectorInterface $schemaInspector = null;

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
            $this->connectSingle($auxConfig);
        } else {
            $this->buildPool($auxConfig);
        }
    }

    private function connectSingle(\stdClass $auxConfig): void
    {
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
                }

                \_trace('+----------------------');
                \_trace('| PDO NOT AVAILABLE! ');
                \_trace('|   at ' . self::$connectionString);
                \_trace('| ' . $th->getMessage() . '');
                \_trace('+----------------------');
            }
        } while (!self::getConnected());
    }

    private function buildPool(\stdClass $auxConfig): void
    {
        $minPool = max(1, min(20, $auxConfig->pool ?? 5));
        \_trace("Building PDO connection pool with $minPool item(s)");
        for ($i = 0; $i < $minPool; $i++) {
            self::$pool[] = new self(true);
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
                $normalized = $this->resolveDriverAdapter()->normalizeError(
                    new \RuntimeException(
                        trim((string) ($errorInfo[0] ?? '')) . ' ' . (string) ($errorInfo[2] ?? 'Database execution error'),
                        (int) ($errorInfo[1] ?? 0)
                    ),
                    $sql,
                    $fParams
                );

                $msg = str_replace("\n", ' ', $sql);
                $msg = preg_replace('/\s+/', ' ', $msg);

                $driverPrefix = strtoupper((string) ($normalized['driver'] ?? 'db'));
                $sqlState = (string) ($normalized['sql_state'] ?? 'UNKNOWN');
                $errMsg = (string) ($normalized['message'] ?? 'Database execution error');
                $driverCode = (int) ($normalized['driver_code'] ?? YeAPF_PDO_CONNECTION);

                throw new \YeAPF\YeAPFException($driverPrefix . '-' . $sqlState . ': ' . $errMsg . " when doing:\n           " . $msg, $driverCode);
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

    public function queryAll($sql, $data = null): array
    {
        $ret = [];
        if (self::getConnected()) {
            $statement = $this->query($sql, $data);
            if ($statement instanceof \PDOStatement) {
                while ($row = $statement->fetch()) {
                    if (is_array($row)) {
                        $ret[] = $row;
                    }
                }
            }
        }

        return $ret;
    }

    public function beginTransaction(): void
    {
        if (self::getConnected() && self::$db instanceof \PDO && !self::$db->inTransaction()) {
            self::$db->beginTransaction();
        }
    }

    public function commitTransaction(): void
    {
        if (self::getConnected() && self::$db instanceof \PDO && self::$db->inTransaction()) {
            self::$db->commit();
        }
    }

    public function rollBackTransaction(): void
    {
        if (self::getConnected() && self::$db instanceof \PDO && self::$db->inTransaction()) {
            self::$db->rollBack();
        }
    }

    public function getServerVersion(): ?string
    {
        if (!self::getConnected() || !(self::$db instanceof \PDO)) {
            return null;
        }

        $version = self::$db->getAttribute(\PDO::ATTR_SERVER_VERSION);
        return is_string($version) ? $version : null;
    }

    public function tableExists($tablename, $schemaname = null)
    {
        return $this->resolveSchemaInspector()->tableExists((string) $tablename, is_string($schemaname) ? $schemaname : null);
    }

    public function columnDefinition($tablename, $columnname, $schemaname = null)
    {
        return $this->resolveSchemaInspector()->columnDefinition((string) $tablename, (string) $columnname, is_string($schemaname) ? $schemaname : null);
    }

    public function columnExists($tablename, $columnname, $schemaname = null)
    {
        return $this->resolveSchemaInspector()->columnExists((string) $tablename, (string) $columnname, is_string($schemaname) ? $schemaname : null);
    }

    public function columns($tablename, $schemaname = null)
    {
        return $this->resolveSchemaInspector()->columns((string) $tablename, is_string($schemaname) ? $schemaname : null);
    }

    private function getConfiguredDriver(): string
    {
        $configDriver = self::$config->pdo->driver ?? 'pgsql';
        return strtolower((string) $configDriver);
    }

    private function resolveDriverAdapter(): DBDriverInterface
    {
        if (null === $this->driverAdapter) {
            $driverName = $this->getConfiguredDriver();
            $this->driverAdapter = self::createDriverAdapter($driverName, $this, $this->resolveSchemaInspector());
        }

        return $this->driverAdapter;
    }

    private function resolveSchemaInspector(): SchemaInspectorInterface
    {
        if (null === $this->schemaInspector) {
            $driverName = $this->getConfiguredDriver();
            $defaultSchema = (string) (self::$config->pdo->schema ?? 'public');
            $this->schemaInspector = self::createSchemaInspector($driverName, $this, $defaultSchema);
        }

        return $this->schemaInspector;
    }

    private static function createDriverAdapter(
        string $driverName,
        PDOConnection $connection,
        SchemaInspectorInterface $schemaInspector
    ): DBDriverInterface
    {
        if ('pgsql' === $driverName) {
            return new PostgreSQLPDOAdapter($connection, $schemaInspector);
        }

        if ('mysql' === $driverName) {
            return new MySQLPDOAdapter($connection, $schemaInspector);
        }

        throw new \YeAPF\YeAPFException(
            'Driver adapter is currently unavailable for `' . $driverName . '` in PDOConnection',
            YeAPF_UNIMPLEMENTED_KEY_TYPE
        );
    }

    private static function createSchemaInspector(
        string $driverName,
        PDOConnection $connection,
        string $defaultSchema
    ): SchemaInspectorInterface
    {
        if ('pgsql' === $driverName) {
            return new PostgreSQLSchemaInspector($connection, $defaultSchema);
        }

        if ('mysql' === $driverName) {
            return new MySQLSchemaInspector($connection, $defaultSchema);
        }

        throw new \YeAPF\YeAPFException(
            'Schema inspector is currently unavailable for `' . $driverName . '` in PDOConnection',
            YeAPF_UNIMPLEMENTED_KEY_TYPE
        );
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
