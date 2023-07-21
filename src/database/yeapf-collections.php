<?php
declare(strict_types=1);
namespace YeAPF\ORM;

use Swoole;
use Swoole\Coroutine;

/**
 * This class is used to simulate a Redis database
 * in order to avoid the problem that surge when the
 * database is not connected.
 */
class VirtualRedis
{
    public function set($name, $value)
    {
    }
    public function get($name)
    {
        return null;
    }
    public function exists($name)
    {
        return false;
    }
    public function delete($name)
    {
    }
    public function clear()
    {
    }
    public function list()
    {
        return [];
    }
    public function getConnected()
    {
        return false;
    }
}

class DocumentModel extends \YeAPF\SanitizedKeyData
{
    private $context;
    private $collectionName;
    private $pskData;

    public function __construct(
        \YeAPF\Connection\PersistenceContext $context,
        string $collectionName
    ) {
        $this->context = $context;
        $this->collectionName = $collectionName;
        $this->pskData = new \YeAPF\ORM\PersistentSanitizedKeyData($context);
        parent::__construct();
    }

    public function getCollectionName()
    {
        return $this->collectionName;
    }

    private function SQLColumnDefinition2Constraint($columnDefinition)
    {
        // echo "[SQLColumnDefinition2Constraint]";
        // echo "\tAnalyzed info: ".json_encode($columnDefinition, JSON_PRETTY_PRINT);
        $constraint = [];
        $constraint['keyName'] = $columnDefinition['column_name'];
        $constraint['default'] = $columnDefinition['column_default'];

        $constraint['acceptNULL'] = ($columnDefinition['is_nullable'] == 'YES') ? true : false;
        $constraint['primary'] = ($columnDefinition['is_primary'] == 1) ? true : false;
        $constraint['unique'] = ($columnDefinition['is_unique'] == 1) ? true : false;
        $constraint['required'] = ($columnDefinition['is_required'] == 1) ? true : false;

        if ($columnDefinition['character_maximum_length'] !== null) {
            $constraint['keyType'] = YeAPF_TYPE_STRING;
            $constraint['length'] = $columnDefinition['character_maximum_length'];
        } else {
            if ("character varying" == $columnDefinition['data_type']) {
                $constraint['keyType'] = YeAPF_TYPE_STRING;
            } else {
                if ("numeric" == substr($columnDefinition['data_type'], 0, 7)) {
                    $constraint['keyType'] = YeAPF_TYPE_FLOAT;
                    $constraint['decimals'] = $columnDefinition['numeric_precision'];
                    $constraint['length'] = $columnDefinition['numeric_scale'];
                } else {
                    switch($columnDefinition['data_type']) {
                        case "boolean":
                            $constraint['keyType'] = YeAPF_TYPE_BOOL;
                            break;

                        case "int":
                        case "integer":
                            $constraint['keyType'] = YeAPF_TYPE_INT;
                            $constraint['length'] = $columnDefinition['numeric_precision'];
                            break;

                        case "date":
                            $constraint['keyType'] = YeAPF_TYPE_DATE;
                            break;

                        case "time":
                            $constraint['keyType'] = YeAPF_TYPE_TIME;
                            break;

                        case "datetime":
                            $constraint['keyType'] = YeAPF_TYPE_DATETIME;
                            break;

                        case "bytea":
                            $constraint['keyType'] = YeAPF_TYPE_BYTES;
                            break;

                        default:
                            new \YeAPF\YeAPFException("Unknown data type", YeAPF_UNKNOWN_DATA_TYPE);
                    }
                }
            }
        }

        return $constraint;
    }

    public function importModelFromDB()
    {
        // echo "Importing model from collection ".$this->collectionName."\n";
        $pdo = null;
        $this->pskData->gainPDOConnection($pdo);
        try {

            $columns = $pdo->columns($this->collectionName);
            // echo "Columns: \n".json_encode($columns, JSON_PRETTY_PRINT);
    
            $pbo = 0;
            foreach($columns as $k => $column) {
                // echo "Column definition: \n".json_encode($column, JSON_PRETTY_PRINT);
                $constraint = self::SQLColumnDefinition2Constraint($column);
                // echo "Resultant constraint: \n".json_encode($constraint, JSON_PRETTY_PRINT);
    
                $this->setConstraint(
                    keyName: $constraint['keyName'],
                    keyType: $constraint['keyType'],
                    length: $constraint['length'],
                    unique: $constraint['unique'],
                    primary: $constraint['primary'],
                    required: $constraint['required'],
                    protobufOrder: $pbo++
                );
            }
        } finally {
            $this->pskData->giveBackPDOConnection($pdo);
        }
        return $this;
    }

    public function getDocumentModelConstraints($voidRegex = false)
    {
        $ret = [];
        foreach($this->getConstraints() as $key => $constraint) {
            $ret[$key] = [];
            foreach($constraint as $field => $value) {
                if (!$voidRegex || $field!='regExpression') {
                    if (null != $value) {
                        $ret[$key][$field] = $value;
                    }
                }
            }
        }
        return $ret;
    }

    public function exportDocumentModel(int $format)
    {
        $ret = null;

        switch($format) {
            case YeAPF_JSON_FORMAT:
                $ret = $this->getDocumentModelConstraints(true);
                $ret = json_encode($ret, JSON_PRETTY_PRINT);
                break;

            case YeAPF_SQL_FORMAT:
                break;

            case YeAPF_PROTOBUF_FORMAT:
                $fields = $this->getDocumentModelConstraints(true);
                $orderedList = [];
                foreach($fields as $key => $constraint) {
                    if (null !== $constraint['protobufOrder']) {
                        $orderedList[$key]=$constraint;
                    }
                }
                uasort($orderedList, function ($a, $b) {
                    return $a['protobufOrder'] - $b['protobufOrder'];
                });

                $ret = "message ".$this->getCollectionName()." {\n";
                foreach($orderedList as $key => $constraint) {
                    $type = $constraint['type'];
                    switch ($type) {
                        case YeAPF_TYPE_BOOL:
                            $type = 'bool';
                            break;
                        case YeAPF_TYPE_INT:
                            $type = 'int32';
                            break;
                        case YeAPF_TYPE_FLOAT:
                            $type = 'float';
                            break;
                        case YeAPF_TYPE_DATE:
                            $type = 'string';
                            break;
                        case YeAPF_TYPE_TIME:
                            $type = 'string';
                            break;
                        case YeAPF_TYPE_DATETIME:
                            $type = 'string';
                            break;
                        case YeAPF_TYPE_STRING:
                            $type = 'string';
                            break;
                        default:
                            throw new \YeAPF\YeAPFException("Unsupported type", YeAPF_UNSUPPORTED_TYPE);
                    }
                    $ret .= "\t".$type." $key = ".$constraint['protobufOrder'].";\n";
                }

                $ret .= "}\n";

                break;

            default:
                throw new \YeAPF\YeAPFException("Unknown document model: '$format'", YeAPF_UNKNOWN_EXPORTABLE_FORMAT);

        }


        return $ret;
    }

    private function getModelsAssetsFolder()
    {
        $folder = \YeAPF\YeAPFConfig::getGLobalAssetsFolder().'/documentModels/';
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        return $folder;
    }

    public function assetsFolderModelExists()
    {
        $folder = $this->getModelsAssetsFolder();
        return file_exists($folder.$this->getCollectionName().'.json');
    }

    public function exportModelToAssetFolder()
    {
        $folder = $this->getModelsAssetsFolder();
        if (!is_writable($folder)) {
            throw new \YeAPF\YeAPFException("Assets folder is not writable", YeAPF_ASSETS_FOLDER_NOT_WRITABLE);
        }
        $json = $this->exportDocumentModel(YeAPF_JSON_FORMAT);
        // echo "\nDIE at ".__FILE__.':'.__LINE__."\n";
        // die(print_r($json));
        return file_put_contents($folder.$this->getCollectionName().'.json', $json);
    }

    public function importModelFromAssetFolder()
    {
        $folder = $this->getModelsAssetsFolder();
        try {
            $json = file_get_contents($folder.$this->getCollectionName().'.json');
        } catch (\Exception $e) {
            throw new \YeAPF\YeAPFException("Couldn't read model file", YeAPF_ASSETS_FOLDER_NOT_READABLE);
        }
        return $this->importModel(YeAPF_JSON_FORMAT, $json);
    }

    public function exportModel(int $format)
    {

    }

    public function importModel(int $format, mixed $model)
    {
        switch($format) {
            case YeAPF_JSON_FORMAT:
                $aux = json_decode($model, true);
                foreach($aux as $constraintName => $constraintDefinition) {
                    \_log("$constraintName = ".json_encode($constraintDefinition));
                    $this->setConstraint(
                        keyName: $constraintName,
                        keyType: $constraintDefinition['type'],
                        length: $constraintDefinition['length'],
                        unique: $constraintDefinition['unique']?true:false,
                        primary: $constraintDefinition['primary']?true:false,
                        required: $constraintDefinition['required']?true:false,
                        protobufOrder: $constraintDefinition['protobufOrder']
                    );
                }
                break;
        }
    }
}


/**
 * SharedSanitizedKeyData is an intermediate class that
 * is used to determine if we're using a real or a virtualized Redis connection.
 */
class SharedSanitizedKeyData extends \YeAPF\SanitizedKeyData
{
    private static $virtualRedis=null;
    private static $context=null;

    /**
     * Constructor for the SharedSanitizedKeyData.
     * It creates a virtual Redis instance, so it can be used
     * if the Redis is not connected.
     *
     * @return void
     */
    public function __construct(\YeAPF\Connection\PersistenceContext $context)
    {
        parent::__construct();
        self::$virtualRedis = new VirtualRedis();
        self::$context = $context;
    }

    /**
     * Returns a Redis connection object.
     * If the Redis is not connected, it returns the virtual connection object.
     * Otherwise, it returns the global connection object.
     *
     * @return RedisConnection Returns a Redis connection object.
     */
    public static function getRedisConnection()
    {
        $aux = self::$context->getRedisConnection()??null;
        if (null==$aux || !$aux->getConnected()) {
            return self::$virtualRedis;
        } else {
            return $aux;
        }
    }
}

/**
 * PersistentSanitizedKeyData is an intermediate class that
 * is used to determine if the database is connected or not.
 * If the database is not connected, then an exception is triggered.
 */
class PersistentSanitizedKeyData extends \YeAPF\ORM\SharedSanitizedKeyData
{
    public function __construct(\YeAPF\Connection\PersistenceContext $context)
    {
        parent::__construct($context);
    }

    /**
     * Retrieves the PDO database connection.
     * Differently from the parent class SharedSanitizedKeyData,
     * it throws an exception if the database is not connected.
     *
     * @todo if the database is not connected, try to connect at least one time
     *
     * @throws \YeAPF\YeAPFException when the database is not connected
     * @return \PDO the PDO database connection
     */
    public static function getPDOConnection()
    {
        global $yeapfPDOConnection;
        _log("[ getPDOConnection ]");

        throw new \YeAPF\YeAPFException("OBSOLETE function getPDOConnection()", YeAPF_OBSOLETE_FUNCTION);

        if (null == $yeapfPDOConnection || !$yeapfPDOConnection->getConnected()) {
            throw new \YeAPF\YeAPFException("Database not connected", YeAPF_PDO_NOT_CONNECTED);

        } else {
            return $yeapfPDOConnection;
        }
    }

    public static function do($func)
    {

        if (null==($mainConn=\YeAPF\Connection\DB\GetMainPDOConnection())) {
            $mainConn = \YeAPF\Connection\DB\CreateMainPDOConnection();
        }

        $conn = null;
        $mainConn->popConnection($conn);
        try {
            $func($conn);
        } finally {
            $mainConn->pushConnection($conn);
        }
    }

    public static function gainPDOConnection(&$pdo):void
    {

        if (null==($mainConn=\YeAPF\Connection\DB\GetMainPDOConnection())) {
            $mainConn = \YeAPF\Connection\DB\CreateMainPDOConnection();
        }

        $mainConn->popConnection($pdo);
    }

    public static function giveBackPDOConnection(mixed $pdo):void
    {

        if (null==($mainConn=\YeAPF\Connection\DB\GetMainPDOConnection())) {
            $mainConn = \YeAPF\Connection\DB\CreateMainPDOConnection();
        }

        $mainConn->pushConnection($pdo);
    }
}

/**
 * This incarnation of \YeAPF\SanitizedKeyData is used to store data
 * that is shared among the plugins classes and objects in the current
 * instance.
 * For that, it uses Redis if the connection is available.
 */
class SharedSanitizedRecord extends \YeAPF\ORM\SharedSanitizedKeyData
{

    public function __set(string $name, mixed $value)
    {
        parent::__set($name, $value);
        self::getRedisConnection()->set($name, $value);
    }

    public function __get(string $name)
    {
        $ret = null;
        if (self::getRedisConnection()->getConnected()) {
            $ret = self::getRedisConnection()->get($name);
        } else {
            $ret = parent::__get($name);
        }
        return $ret;
    }

    public function __isset($name)
    {
        $ret = false;
        if (self::getRedisConnection()->getConnected()) {
            $ret = self::getRedisConnection()->exists($name);
        } else {
            $ret = parent::__isset($name);
        }
        return $ret;
    }

    public function __unset($name)
    {
        $ret = false;
        self::getRedisConnection()->delete($name);
        $ret = parent::__unset($name);
        return $ret;
    }

    public function set($name, $value)
    {
        return self::__set($name, $value);
    }

    public function get($name)
    {
        return self::__get($name);
    }

    public function delete($name)
    {
        return self::__unset($name);
    }

    public function clear()
    {
        throw new \YeAPF\YeAPFException("Not implemented", YeAPF_METHOD_NOT_IMPLEMENTED);
    }

    public function keys()
    {
        $aux = self::getRedisConnection()->keys();
        $ret = array_filter(
            $aux,
            function ($key) {
                return !(
                    in_array(
                        self::getRedisConnection()->type($key),
                        [
                            \Redis::REDIS_LIST,
                            \Redis::REDIS_SET,
                            \Redis::REDIS_HASH,
                            \Redis::REDIS_ZSET
                        ]
                    )
                );
            }
        );

        return $ret;
    }
}



/**
 * This is an interface definition for a collection of documents.
 * It requires implementations to have methods for getting/setting/deleting documents,
 * checking if a document exists or not, and listing all documents in the collection.
 * It also has a method for finding a document by a given sample.
 */
interface iCollection
{
    public function __construct(
        \YeAPF\Connection\PersistenceContext $context,
        string $collectionName,
        string $collectionIdName = 'id',
        \YeAPF\ORM\DocumentModel $documentModel = null
    );

    public function getCollectionName();
    public function getCollectionIdName();

    public function hasDocument(string $id);
    public function getDocument(string $id);
    public function setDocument(string|null $id, mixed &$data);
    public function deleteDocument(string $id);

    public function listDocuments();

    public function findByExample(mixed $example);
    public function subsetByExample(mixed $example, int $count, int $start=0);
}


/**
 * SharedSanitizedCollection is an implementation of iCollection that was written
 * in order to mantain a collection of documents that can be shared with other
 * connected clients through redis client.
 * It's database oriented and not session oriented.
 * That means any connected session has complete access to shared data.
 */
class SharedSanitizedCollection extends \YeAPF\ORM\SharedSanitizedKeyData implements iCollection
{
    private \YeAPF\ORM\DocumentModel|null $documentModel;
    private string $collectionName;
    private string $collectionIdName;

    public function getCollectionName()
    {
        return $this->collectionName??null;
    }

    public function getCollectionIdName()
    {
        return $this->collectionIdName??null;
    }

    public function __construct(
        \YeAPF\Connection\PersistenceContext $context,
        string $collectionName,
        string $collectionIdName = 'id',
        \YeAPF\ORM\DocumentModel $documentModel = null
    ) {
        parent::__construct($context);
        $this->collectionName = $collectionName;
        $this->collectionIdName = $collectionIdName;
        $this->documentModel = $documentModel;
    }

    public function getDocumentModel()
    {
        return $this->documentModel;
    }

    public function exportDocumentModel(int $format)
    {
        $ret = null;
        if (null == $this->getDocumentModel()) {
            throw new \YeAPF\YeAPFException("Document model not set", YeAPF_DOCUMENT_MODEL_NOT_SET);
        } else {
            $ret = $this->getDocumentModel()->exportDocumentModel($format);
        }
        return $ret;
    }

    public function hasDocument(string $id)
    {
        return $this->getRedisConnection()->hget($this->collectionName.":$id", $this->collectionIdName) == $id ;
    }

    public function collectionExists()
    {
        $ret = false;
        if ($this->getRedisConnection()->getConnected()) {
            $ret = $this->getRedisConnection()->exists($this->collectionName);
        }
        return $ret;
    }

    public function getDocument(string $id)
    {
        $ret = false;
        if ($this->getRedisConnection()->getConnected()) {
            $ret = $this->getRedisConnection()->hgetall("$this->collectionName:$id");
        }
        return $ret;
    }

    public function setDocument(string|null $id, mixed &$data)
    {
        $ret = false;
        if (null == $id || 0==strlen(trim($id))) {
            $id = \YeAPF\generateUniqueId();
        }

        if ($this->getRedisConnection()->getConnected()) {
            $data[$this->collectionIdName] = $id;
            $ret = $this->getRedisConnection()->hset("$this->collectionName:$id", $data);
        }
        return $ret;
    }


    public function deleteDocument(string $id)
    {
        $ret = false;
        if ($this->getRedisConnection()->getConnected()) {
            $ret = $this->getRedisConnection()->delete("$this->collectionName:$id");
        }
        return $ret;
    }

    public function listDocuments()
    {
        $ret = [];
        if ($this->getRedisConnection()->getConnected()) {
            $aux = array_filter(
                $this->getRedisConnection()->keys("$this->collectionName:*"),
                function ($key) {
                    return $this->getRedisConnection()->type($key) == \Redis::REDIS_HASH;
                }
            );

            foreach($aux as $key) {
                $ret[] = substr($key, strlen($this->collectionName)+1);
            }
        }
        return $ret;
    }

    /**
     * Lookup for the first ocurrence of a document where the fields
     * present in the example have the same values in the document found.
     *
     * @param mixed $example
     *
     * @return mixed
     */
    public function findByExample(mixed $example)
    {
        return $this->subsetByExample($example, 1)[0]??false;
    }

    /**
     * Returns a subset of documents from the collection that match the given example.
     *
     * @param mixed[] $example An associative array where keys are field names and values are sample values.
     * @param int $count The maximum number of documents to return.
     * @param int $start The index of the first document to return.
     * @return mixed[] An array containing the matching documents.
     */
    public function subsetByExample($example, $count, $start=0)
    {
        $ret = [];
        if ($this->getRedisConnection()->getConnected()) {
            $pos=0;
            foreach($this->listDocuments() as $id) {
                $documentFound = true;
                foreach($example as $fieldName => $sampleValue) {
                    $auxValue = $this->getRedisConnection()->hget("$this->collectionName:$id", $fieldName);
                    if ($auxValue != $sampleValue) {
                        $documentFound=false;
                        break;
                    }
                }
                if ($documentFound) {
                    if ($pos>=$start) {
                        $ret[]=$this->getDocument($id);
                    }
                    $pos++;
                    if ($pos>=$count) {
                        break;
                    }
                }
            }
        }
        return $ret;
    }

}

/**
 * PersistentCollection aims to be a cacheable persistent collection of structured documents.
 * It was thinking to be used with well structured data in mind, so it
 * uses a model that can be created using the YeAPF\SanitizedKeyData
 *
 * @example /examples/persistent-data.php
 */
class PersistentCollection extends \YeAPF\ORM\SharedSanitizedCollection implements iCollection
{
    private \YeAPF\ORM\PersistentSanitizedKeyData $pskData;
    private $cacheMode;

    /**
     * Constructor for the class that sets the collection name, collection ID name,
     * document model and cache mode. Throws an exception if an invalid cache mode is
     * passed.
     *
     * @param string $collectionName Name of the collection
     * @param string $collectionIdName Name of the ID field, default: 'id'
     * @param \YeAPF\ORM\DocumentModel|null $documentModel Instance of the document model
     * @param int $cacheMode Cache mode, defaults to YeAPF_SAVE_CACHE_FIRST
     * @throws \YeAPF\YeAPFException If an invalid cache mode is passed
     * @return void
     */
    public function __construct(
        \YeAPF\Connection\PersistenceContext $context,
        string $collectionName,
        string $collectionIdName = 'id',
        \YeAPF\ORM\DocumentModel $documentModel = null,
        int $cacheMode=YeAPF_SAVE_CACHE_FIRST
    ) {
        $cachedEnabledModes = [ YeAPF_SAVE_CACHE_FIRST, YeAPF_SAVE_CACHE_LAST ];

        if (in_array($cacheMode, $cachedEnabledModes)) {
            $this->cacheMode = $cacheMode;
            $this->pskData = new \YeAPF\ORM\PersistentSanitizedKeyData($context);
            parent::__construct($context, $collectionName, $collectionIdName, $documentModel);
            $this->grantCollection();
            _log(" * ".__LINE__."");
        } else {
            throw new \YeAPF\YeAPFException("Invalid cache mode", YeAPF_INVALID_CACHE_MODE);
        }

    }

    public function grantCollection()
    {

        _log(">>> Asking for connection");
        $pdo = null;
        $this->pskData->gainPDOConnection($pdo);
        _log(">>> Ready to work");
        // print_r($pdo);
        try {
            _log(" * ".__LINE__."");
            if (!$pdo -> tableExists(self::getCollectionName())) {
                $sql = self::exportDocumentModel(YeAPF_SQL_FORMAT);
                $ret = $pdo -> query($sql);
            }
            _log(" * ".__LINE__."");

            foreach($this->getDocumentModel()->getConstraints() as $key => $constraint) {
                _log("  * ".__LINE__." $key ".json_encode($constraint));
                $columnDefinition = $key ." ".self::internalType2SQLType($constraint);

                if (false == $constraint['acceptNULL']) {
                    $columnDefinition .= " not null ";
                }

                if (true == $constraint['unique']) {
                    $columnDefinition .= " unique ";
                } elseif (true == $constraint['primary']) {
                    $columnDefinition .= " primary key ";
                }

                $colDef = $pdo -> columnDefinition(self::getCollectionName(), $key);

                if (empty($colDef)) {
                    $sql="alter table ".self::getCollectionName()." add column ".$columnDefinition;

                    $retAlter = $pdo -> query($sql);
                    if (!$retAlter) {
                        throw new \YeAPF\YeAPFException("Error adding column $key", YeAPF_ERROR_ADDING_COLUMN);
                    }
                } else {
                    $internalColDef = $this->constraint2SQLColumnDefinition($key, $constraint);
                    $diff = array_diff($internalColDef, $colDef);

                    if (!empty($diff)) {
                        _log("Database Column Definition:");
                        print_r($colDef);
                        _log("Internal Column Definition:");
                        print_r($internalColDef);
                        _log("Differences:");
                        print_r($diff);
                        foreach($diff as $colDefKey => $colDefValue) {
                            $sql="alter table ".self::getCollectionName();
                            switch ($colDefKey) {
                                case 'column_name':
                                    $sql.=" rename $key to ".$colDefValue;
                                    break;

                                case 'column_type':
                                    $sql.=" alter $key TYPE ".$colDefValue;
                                    break;

                                case 'column_default':
                                    $sql.=" alter $key default ".$colDefValue;
                                    break;

                                case 'is_nullable':
                                    if ($colDefValue=="YES") {
                                        $sql.=" alter $key drop not null";
                                    } else {
                                        $sql.=" alter $key set not null";
                                    }
                                    break;

                                case 'numeric_precision':
                                case 'numeric_scale':
                                    $typeDef = $this->internalType2SQLType($constraint);
                                    // $typeDef = substr($typeDef, strpos(" ",$typeDef));
                                    $sql.=" alter $key TYPE $typeDef";
                                    break;

                                default:
                                    die("\n$colDefKey ... e agora?\n");
                                    break;
                            }
                        }

                        $retAlter = $pdo -> query($sql);
                    }
                }
            }
            _log(" * ".__LINE__."");

        } finally {
            $this->pskData->giveBackPDOConnection($pdo);
        }


    }

    private function internalType2SQLType($constraint)
    {
        $ret = null;
        switch ($constraint['type']) {
            case YeAPF_TYPE_BOOL:
                $ret = "boolean ";
                break;

            case YeAPF_TYPE_INT:
                $ret = "integer ";
                break;

            case YeAPF_TYPE_FLOAT:
                if (null==$constraint['decimals']) {
                    if (null==$constraint['length']) {
                        $ret.= "numeric ";
                    } else {
                        $ret.= "numeric( ".$constraint['length']." ) ";
                    }
                } else {
                    $ret .= "numeric( ".$constraint['length'].",  ".$constraint['decimals']." ) ";
                }
                break;

            case YeAPF_TYPE_DATE:
                $ret = "date ";
                break;

            case YeAPF_TYPE_TIME:
                $ret = "time ";
                break;

            case YeAPF_TYPE_DATETIME:
                $ret = "datetime ";
                break;

            case YeAPF_TYPE_STRING:
                if (null==$constraint['length']) {
                    $ret= "character varying ";
                } else {
                    $ret = "character varying( ".$constraint['length']." ) ";
                }
                break;

            case YeAPF_TYPE_BYTES:
                $ret = "bytea ";
                break;

            default:
                throw new \YeAPF\YeAPFException("Unsupported type", YeAPF_UNSUPPORTED_TYPE);
        }

        return $ret;
    }

    private function constraint2SQLColumnDefinition($name, $constraint)
    {
        $ret = [];
        $name = strtolower($name);
        $ret['column_name']=$name;
        $ret['column_default']=$constraint['default']??null;
        $ret['is_nullable'] = ($constraint['acceptNULL']??false)?"YES":"NO";
        $ret['data_type']=trim(explode('(', $this->internalType2SQLType($constraint))[0]);

        $ret['character_maximum_length'] = null;
        $ret['numeric_precision'] = null;
        $ret['numeric_scale'] = null;
        if (strpos($ret['data_type'], 'char')==false) {
            if ($ret['data_type']!='date') {
                // _log($name." ".$ret['data_type']." ".strpos($ret['data_type'], 'date')." | ".strpos($ret['data_type'], 'time'));
                $ret['numeric_precision'] = $constraint['decimals']??null;
                $ret['numeric_scale'] = $constraint['length']??null;
            }
        } else {
            $ret['character_maximum_length'] = $constraint['length']??null;
        }
        return $ret;
    }


    public function exportDocumentModel(int $format)
    {
        $ret = null;
        // echo "*********\n";
        if (null == $this->getDocumentModel()) {
            throw new \YeAPF\YeAPFException("Document model not set", YeAPF_DOCUMENT_MODEL_NOT_SET);
        } else {
            if (YeAPF_SQL_FORMAT==$format) {
                $c=0;
                $ret = "create table if not exists ".$this->getCollectionName()." (\n  ";
                foreach($this->getDocumentModel()->getConstraints() as $key => $constraint) {
                    // $constraint = $this->documentModel->getConstraint($key);
                    if ($c++>0) {
                        $ret.= ",\n  ";
                    }
                    $ret .= $key . " ".self::internalType2SQLType($constraint);

                    if (false == $constraint['acceptNULL']) {
                        $ret .= "not null ";
                    }

                    if (true == $constraint['unique']) {
                        $ret .= "unique ";
                    } elseif (true == $constraint['primary']) {
                        $ret .= "primary key ";
                    }
                }
                $ret .= "\n);";
            } else {
                // echo "  ( parent )\n";
                $ret = parent::exportDocumentModel($format);
                // echo "   ret = ".print_r($ret, true)."\n";
            }
        }

        return $ret;
    }

    private function hasDocumentInDatabase($id)
    {
        $ret = null;
        $sql="select exists(select 1 from ".$this->getCollectionName()." where id=:id)";
        $params = [ $this->getCollectionIdName() => $id ];
        $this->pskData->do(function ($persistentData) use ($sql, $params, &$ret) {
            $auxRet = $persistentData->queryAndFetch($sql, $params);
            $ret = (is_array($auxRet) && $auxRet['exists']??false);
            // \_log("IS ARRAY?".(is_array($auxRet)?"true":"false"));
            // \_log("exists? ".$auxRet['exists']);
            // \_log(print_r($auxRet, true));
            // \_log("ret = ".($ret?"true":"false"));
        });
        _log("HasDocument $id in ".$this->getCollectionName()."? " .($ret?"true":"false"));
        return $ret;
    }

    private function saveDocumentInDatabase(string $id, mixed &$data)
    {
        $data[$this->getCollectionIdName()] = $id;
        if ($this->hasDocumentInDatabase($id)) {
            $sql = sprintf("update %s set ", $this->getCollectionName());
            $c=0;
            foreach($data as $key => $value) {
                if (is_object($value)) {
                    continue;
                }
                if ($c++>0) {
                    $sql.=", ";
                }
                $sql.=sprintf("%s=:%s ", $key, $key);
            }
            $sql.=sprintf("where %1s = :%1s", $this->getCollectionIdName(), $this->getCollectionIdName());
        } else {
            $sql = sprintf("insert into %s ", $this->getCollectionName(), $this->getCollectionIdName());
            $sql .= "(";
            $c=0;
            foreach($data as $key => $value) {
                if (is_object($value)) {
                    continue;
                }
                if ($c++>0) {
                    $sql.=",";
                }
                $sql.=sprintf("%s", $key);
            }
            $sql .= ") values (";
            $c=0;
            foreach($data as $key => $value) {
                if (is_object($value)) {
                    continue;
                }
                if ($c++>0) {
                    $sql.=",";
                }
                $sql.=":".$key;
            }
            $sql .= ")";
        }
        if ($data instanceof \YeAPF\KeyData) {
            $params = $data->exportRawData();
        } else {
            $params = $data;
        }
        $this->pskData->do(
            function ($conn) use ($sql, &$ret, $params) {
                $auxRet = $conn ->query($sql, $params);
            }
        );



        return $auxRet;
    }

    public function hasDocument(string $id)
    {
        $ret = parent::hasDocument($id);
        if (!$ret) {
            $ret = $this->hasDocumentInDatabase($id);
        }
        return $ret;
    }

    public function getDocument(string $id)
    {
        if (parent::hasDocument($id)) {
            $ret = new \YeAPF\SanitizedKeyData();
            $ret -> importData(parent::getDocument($id));
        } else {
            $sql = "select * from ".$this->getCollectionName()." where ".$this->getCollectionIdName()."=:id";
            $params = [ $this->getCollectionIdName() => $id ];
            // $ret = $this->pskData->getPDOConnection()->queryAndFetch($sql, $params);

            $this->pskData->do(
                function ($conn) use ($sql, &$ret, $params) {
                    $data = $conn ->queryAndFetch($sql, $params);
                    parent::setDocument($id, $data);
                    $ret = new YeAPF\SanitizedKeyData();
                    $ret -> importData(parent::getDocument($id));
                }
            );

        }
        return $ret;
    }

    public function setDocument(string|null $id, mixed &$data)
    {
        /**
         * Here is the dilema: If I update a document in the cache first,
         * and the update in the database fails, the cache will contain wrong data
         * as it does not correlate with the database.
         * On the other side, if I update the database first, there will be a time
         * when the cache will be in a false state because the data could be so
         * important that the application could not relay on cache.
         * As the responsability of how the data is stored in the cache is
         * of the application, we defined two options:
         *    YeAPF_SAVE_CACHE_FIRST and YeAPF_SAVE_CACHE_LAST
         * that are mutually exclusive and present at the instantiation
         * of this class.
         */

        if (null == $id || 0==strlen(trim($id))) {
            $id = \YeAPF\generateUniqueId();
        }

        if (YeAPF_SAVE_CACHE_FIRST == $this->cacheMode) {
            parent::setDocument($id, $data);
            $this->saveDocumentInDatabase($id, $data);
        } else {
            $this->saveDocumentInDatabase($id, $data);
            parent::setDocument($id, $data);
        }
    }

    public function deleteDocument(string $id)
    {
        parent::deleteDocument($id);
        $sql="delete from ".$this->getCollectionName()." where id=:id";
        $params = [ $this->getCollectionIdName() => $id ];
        $this->pskData->do(
            function ($conn) use ($sql, $params) {
                $conn->query($sql, $params);
            }
        );
        // $this->pskData->getPDOConnection()->query($sql, $params);
    }


    public function listDocuments()
    {

    }


    public function findByExample($example)
    {
        $data = $this->subsetByExample($example, 1)[0]??false;
        $ret = new \YeAPF\SanitizedKeyData();
        $ret->importData($data);
        return $ret;
    }

    public function subsetByExample($example, $count, $start=0)
    {
        $sql="select ".$this->getCollectionIdName()." from ".$this->getCollectionName()." where ";
        $c=0;
        foreach($example as $fieldName => $value) {
            if ($c++>0) {
                $sql.=" and ";
            }
            $sql.=sprintf("%s=:%s ", $fieldName, $fieldName);
        }
        if ($count<=0) {
            $sql.="offset $start";
        } else {
            $sql.="limit $count offset $start";
        }

        $sql ="select * from ".$this->getCollectionName()." where ".$this->getCollectionIdName()." in (".$sql.")";


        if ($example instanceof \YeAPF\KeyData) {
            $params = $example->exportData();
        } else {
            $params = $example;
        }

        _log("SQL: $sql");
        _log("PARAMS: ".json_encode($params));

        $ret = [];
        $this->pskData->do(
            function ($conn) use ($sql, &$ret, $params) {
                if (empty($params)) {
                    _log("WARNING: NO PARAMS");
                } else {
                    _log("CONN: ".json_encode($conn));
                    $stmt = $conn ->query($sql, $params);
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $ret[] = $row;
                    }
                }
            }
        );

        return $ret;
    }


}
