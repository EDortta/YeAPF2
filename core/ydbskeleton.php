<?php

interface YDBLink {
  function __construct($server, $mode = "rw");

  public function lastError();

  public function mode(); // ro / rw

  public function name();
}

interface YDBCollection {
  function __construct($roLink, $rwLink, $collectionName, $id_name = "id");

  public function name();

  public function query($aQuery, $offset = 0, $limit = 100);

  public function get($IdOrCondition);

  public function set($aData);

  public function delete($IdOrCondition);
}

class YDBHelper {
  public function kindOfExpression($expr) {
    $ret = null;
    if (null !== $expr) {
      if (is_array($expr)) {
        $ret = 'array';
      } elseif (is_bool($expr)) {
        $ret = 'bool';
      } elseif (is_integer($expr)) {
        $ret = 'int';
      } elseif (is_string($expr)) {
        $ret = 'string';
        preg_match('/[a-z0-9]{32}/', $expr, $expr_analise);
        if ($expr_analise && $expr_analise[0] == $expr) {
          $ret = 'md5';
        } else {
          preg_match('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/', $expr, $expr_analise);
          if ($expr_analise && $expr_analise[0] == $expr) {
            $ret = 'uuid';
          } else {
            preg_match('/[0-9]*/', $expr, $expr_analise);
            if ($expr_analise && $expr_analise[0] == $expr) {
              $ret = 'int';
            } else {
              preg_match('/true|false|(.*)([<>=\(\)]|like)(.*)/', $expr, $expr_analise);
              if ($expr_analise && $expr_analise[0] == $expr) {
                $ret = 'expr';
              }
            }
          }
        }
      }
    }

    return $ret;
  }
}

define("YDBCONFIG_ERROR", 1000);
define("YDB_MISSING_DATABASE", 1001);
define("YDB_MISSING_CONNECTION", 1002);
define("YDB_QUERY_ERROR", 1010);

class YException extends Exception {
  static $details;
  static $errorNumber;
  static $errorDetails;

  public function __construct($message, $code = 0, Exception $previous = null) {
    self::$errorNumber  = $code;
    self::$errorDetails = $message;
    parent::__construct($message, $code, $previous);
  }

  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->errorDetails}\n";
  }

  public function message() {
    return "YDatabase error: ".self::$errorNumber. " ".self::$errorDetails;
  }
}

class DummyCollection extends YDBHelper implements YDBCollection {
  function __construct($roLink, $rwLink, $collectionName, $id_name = "id") {
    return null;
  }

  public function name() {
    return null;
  }

  public function query($aQuery, $offset = 0, $limit = 100) {
    return null;
  }

  public function get($IdOrCondition) {
    return null;
  }

  public function set($aData) {
    return null;
  }

  public function delete($IdOrCondition) {
    return null;
  }

}

class DBConnector {
  public static $links;
  public static $connections;
  public static $collections;
  public static $defaultDBTagName;

  static function init() {
    self::$links       = [];
    self::$connections = [
      'rw' => [],
      'ro' => [],
    ];
    self::$collections      = [];
    self::$defaultDBTagName = null;
  }

  static function registerLink($DBLinkName, $DBLink, $DBCollection) {
    _log("Registering '$DBLinkName' link");
    self::$links[$DBLinkName] = [
      'conn'       => $DBLink,
      'collection' => $DBCollection,
    ];
  }

  static function connect($dbTagName, $server, $mode = 'rw') {
    $ret = false;
    if (count(array_keys(self::$links)) == 0) {
      _die("Call registerLink() before");
    } else {
      $mode    = trim(mb_strtolower($mode));
      $auxMode = ($mode == "ro" ? 0 : ($mode == "rw" ? 1 : -1));
      /* protect password for messages */
      preg_match('/(.*)\/(.*)/', $server, $server_config);
      if ($server_config) {
        $serverMasked = $server_config[1] . "/" . md5($server_config[2] . date('U'));
        if (in_array($auxMode, [0, 1])) {
          /* get server type name */
          preg_match('/(.*)#(.*)$/i', $server, $server_config);
          $driverName       = $server_config[1];
          $serverDefinition = $server_config[2];
          /**
           * verify the connection is not already done
           * rw connections are just one per dbTagName
           * ro can be as many as the programmer desire/can
           **/
          $connExists = array_key_exists($dbTagName, self::$connections[$mode]);
          if (!$connExists || $mode == 'ro') {
            /* verify there is a link at reach */
            if (!empty(self::$links[$driverName])) {
              _log("Instantiating dbTagName: $dbTagName mode: $auxMode");
              $aux = new self::$links[$driverName]['conn']($serverDefinition);

              if (empty(self::$connections[$mode][$dbTagName])) {
                self::$connections[$mode][$dbTagName] = [];
                if (self::$defaultDBTagName == null) {
                  _log("Default dbTagName: $dbTagName");
                  self::$defaultDBTagName = $dbTagName;
                }
                _log("Connections pool changed to " . json_encode(self::$connections));
              }

              /* rw mode always allows just one connection per dbTagName */
              if ($mode == 'rw') {
                $connNdx = 0;
              } else {
                $connNdx = count(self::$connections[$mode][$dbTagName]);
              }
              self::$connections[$mode][$dbTagName][$connNdx] = $aux;
              $ret                                            = $aux;
            } else {
              _die("Unknown link: '$driverName' connecting to $serverMasked");
            }
          } else {
            $ret = self::$connections[$mode][$dbTagName][0];
          }
        } else {
          _die("Unknown mode '$mode' when connecting to $serverMasked");
        }
      } else {
        _die("Password not defined in '$server'");
      }
    }
    return $ret;
  }

  static function grantCollection($dbTagName, $collectionName, $idName = 'id') {
    $ret = new DummyCollection(null, null, null, null);
    if (null === $dbTagName) {
      $dbTagName = self::$defaultDBTagName;
      _log("Using default dbTagName: " . $dbTagName);
    }
    $collName = "$dbTagName.$collectionName";
    _log("Granting $collName");
    if (array_key_exists($collName, self::$collections)) {
      _log("Collection being reused");
      $ret = self::$collections[$collName];
    } else {
      _log("Collection being created");
      if (array_key_exists($dbTagName, _getValue(self::$connections, 'rw', []))) {
        _log("Connection $dbTagName found");
        $rw       = self::$connections['rw'][$dbTagName][0];
        $ro_count = count(_getValue(self::$connections['ro'], $dbTagName, []));
        if ($ro_count == 0) {
          $ro = $rw;
        } else {
          $ro_ndx = mt_rand(0, $ro_count - 1);
          $ro     = self::$connections['ro'][$dbTagName][$ro_ndx];
        }
        $driverName = $rw->driver;
        if (!empty(self::$links[$driverName])) {
          self::$collections[$collName] = new self::$links[$driverName]['collection']($ro, $rw, $collectionName, $idName);
          $ret                          = self::$collections[$collName];
        } else {
          _log("Unknown link: '$driverName' granting collection $collName");
        }
      } else {
        _log("Unknown connection: $dbTagName");
      }
    }
    return $ret;
  }

  static function getLastError() {
    $errorList = [];
    foreach (self::$connections as $connMode) {
      foreach ($dbTagName as $conn) {
        for($i=0; $i<count($conn); $i++) {
          $aux = $conn[$i]->lastError();
          if ($aux['code'] > 0) {
            $errorList[] = $aux;
          }
        }
      }
    }
    return $errorList;
  }

}

DBConnector::init();