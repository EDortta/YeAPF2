<?php
declare(strict_types=1);
namespace YeAPF\Connection\DB;

// use Swoole;
use Swoole\Coroutine;
// use Swoole\Coroutine\Channel;
// use Swoole\Coroutine\PostgreSQL;

class PDOConnection extends \YeAPF\Connection\DBConnection
{
    private static $config;
    private static $db;
    private static $pool;

    private function connect(bool $asPool=true) {
        global $yAnalyzer;
        $auxConfig = self::$config->pdo??new \stdClass();


        echo "Trying to connect to Database Server (PDO)\n";

        $connectionString = $yAnalyzer->do("#(driver):host=#(server);port=#(port);dbname=#(dbname)",json_decode(json_encode($auxConfig),true));
        echo "connectionString: '$connectionString'\n";

        self::setConnected(false);

        if ($asPool){
            echo "Creating PDO Connection pool\n";
            self::$pool = new \Swoole\Coroutine\Channel(5);

            Coroutine::create(function() {
                $connectedCounter = 0;
                for ($i=0; $i<self::$pool->capacity; $i++) {
                    echo "Creating child #$i\n";
                    try {
                        $aux = new self(asPool: false);
                        self::$pool->push($aux);

                        $connectedCounter++;
                    } catch (\Throwable $th) {

                        if ($auxConfig->halt_on_error??false) {
                            throw new \YeAPF\YeAPFException( $th->getMessage(), YeAPF_PDO_CONNECTION, $th);
                        } else {
                            echo "+----------------------\n";
                            echo "| PDO NOT AVAILABLE! \n";
                            echo "| ".$th->getMessage()."\n";
                            echo "+----------------------\n";
                        }
                    }
                }
            });

        } else {
            do {
                try {
                    self::$db = new \PDO($connectionString, $auxConfig->user??'VoidUserName', $auxConfig->password??'VoidPassword');
                    self::$db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING );
                    self::setConnected(true);
                } catch (\Throwable $th) {
                    self::setConnected(false);
                    if ($auxConfig->halt_on_error??false) {
                        throw new \YeAPF\YeAPFException( $th->getMessage(), YeAPF_PDO_CONNECTION, $th);
                    } else {
                        echo "+----------------------\n";
                        echo "| PDO NOT AVAILABLE! \n";
                        echo "| ".$th->getMessage()."\n";
                        echo "+----------------------\n";
                    }
                }
            } while (!self::getConnected());
        }

    }

    public function popConnection(&$pdo) {
        echo ">>> Searching for a connection\n";
        // $coroutine = go(function () use (&$pdo) {
        //     $pdo = null;
        //
        //     $pdo = self::$pool->pop();

        //     echo ">>> Returning a connection\n";
        //     print_r($pdo);
        //     return $pdo;
        // });
        $pdo = self::$pool->pop();
    }

    public function pushConnection($pg) {

        echo ">>> Pushing a connection\n";
        // go(function () use ($pg) {
        //     self::$pool->push($pg);
        // });

        self::$pool->push($pg);
    }

    public function __construct(bool $asPool=true) {
        self::$config = parent::__construct() -> config ?? null;
        self::connect($asPool);
    }

    public function query($sql, $data=null) {
        $ret = null;
        if (self::getConnected()) {
            $sql = trim($sql);

            // echo "SQL: $sql\n";
            // echo "CMD: [$cmd]\n";

            $ret = self::$db->prepare($sql);
            $ret->execute($data);
            $errorInfo = $ret->errorInfo();
            if ('00000'!==$errorInfo[0]) {

                // echo "RET Error Info:\n";
                // print_r($ret->errorInfo());

                $msg = str_replace("\n", " ", $sql);
                $msg = preg_replace('/\s+/', ' ', $msg);

                throw new \YeAPF\YeAPFException('PGSQL-'.$errorInfo[0].': '.$errorInfo[2]. " when doing:\n           ".$msg, $errorInfo[1]);
            }
        }
        return $ret;
    }

    public function queryAndFetch($sql, $data = null) {
        $ret = false;
        if (self::getConnected()) {
            $sql=trim($sql);
            $cmd = explode(' ', $sql)[0]??'';

            $stmt = self::query($sql, $data);

            if ($stmt){
                // echo "stmt:\n";
                // print_r($stmt);

                if (strcasecmp($cmd, 'SELECT')==0) {
                    $ret = $stmt->fetch();

                    // echo "after fetch\n";
                    // print_r($ret);
                }
            }

        }
        return $ret;
    }

    public function tableExists($tablename, $schemaname = null) {
        if (null == $schemaname || '' == trim($schemaname)) {
            $schemaname = self::$config->pdo->schema;
        }

        $sql="select exists(select 1 from pg_tables where tablename=:tablename and schemaname=:schemaname)";
        $params = [
            'schemaname' => $schemaname,
            'tablename' => $tablename
        ];
        $ret = self::queryAndFetch($sql, $params);
        return (is_array($ret) && $ret['exists']??false);
    }

    public function columnDefinition($tablename, $columnname, $schemaname = null) {
        if (null == $schemaname || '' == trim($schemaname)) {
            $schemaname = self::$config->pdo->schema;
        }
        $tablename = strtolower($tablename);
        $schemaname = strtolower($schemaname);
        $columnname = strtolower($columnname);

        $sql = "select column_name, column_default, is_nullable, data_type, character_maximum_length, numeric_precision, numeric_scale from information_schema.columns where table_schema=:schemaname and table_name=:tablename and column_name=:columnname";
        $params = [
            'schemaname' => $schemaname,
            'tablename' => $tablename,
            'columnname' => $columnname
        ];
        $ret = self::queryAndFetch($sql, $params);
        return $ret;
    }

    public function columnExists($tablename, $columnname, $schemaname = null) {
        if (null == $schemaname || '' == trim($schemaname)) {
            $schemaname = self::$config->pdo->schema;
        }

        $tablename = strtolower($tablename);
        $schemaname = strtolower($schemaname);
        $columnname = strtolower($columnname);

        $sql="select column_name from information_schema.columns where table_schema=:schemaname and table_name=:tablename and column_name=:columnname";
        $params = [
            'schemaname' => $schemaname,
            'tablename' => $tablename,
            'columnname' => $columnname
        ];
        $ret = self::queryAndFetch($sql, $params);

        // echo "SQL: $sql\n";
        // echo "ret = ".json_encode($ret)."\n";

        if ($ret!==false) {
            $ret = strcasecmp($ret['column_name']??'',$columnname)==0;
            // echo "$tablename.$columnname Exists? ".($ret?"Yes":"No")."\n";
        }
        return $ret;
    }
}

global $yeapfPDOConnection;
$yeapfPDOConnection = new PDOConnection();