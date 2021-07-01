<?php

(@include_once (__DIR__ . "/ydbskeleton.php")) || die("Error loading ydbskeleton.php");
class MySQLLink implements YDBLink {
  /**
   * server is formed as follows:
   *   host|port:user@database/password
   **/
  function __construct($server, $mode = "rw") {
    $mode = trim(mb_strtolower($mode));

    preg_match('/(.*):(.*)@(.*)\/(.*)$/i', $server, $server_config);
    $this->connection    = null;
    $this->driver        = 'mysql';
    $this->driver_flavor = 'mysqli';
    $this->mode          = ($mode == "ro" ? 0 : ($mode == "rw" ? 1 : -1));

    _dumpY(DBG_DATABASE, 1, "Configuring mysql link");

    if ($server_config) {
      if ($server_config) {
        $aux        = explode("|", $server_config[1]);
        $host       = $aux[0];
        $port       = _getValue($aux, 1, '3306');
        $user       = $server_config[2];
        $database   = $server_config[3];
        $password   = $server_config[4];
        $this->name = $database;
        if (in_array($this->mode, [0, 1])) {
          if (function_exists("mysqli_connect")) {
            _dumpY(DBG_DATABASE, 1, "Connecting to $database ( $host, $user, $password, $database, $port )");
            $this->connection = mysqli_connect($host, $user, $password, $database, $port);
            if ($this->connection) {
              _dumpY(DBG_DATABASE, 1, "DB connection ready");
            } else {
              $connDetail = "";
              if (mysqli_connect_errno()) {
                $connDetail = "Failed to connect to MySQL: " . mysqli_connect_error();
              }
              _die("DB connection rejected ( $connDetail )");

            }
          } else {
            _die("php-mysqli not installed");
          }
        } else {
          _die("Undefined '$mode' mode");
        }
      } else {
        _die("Syntax error in link configuration");
      }
    } else {
      _die("Server configuration not defined");
    }

    if ($this->connection == null) {
      throw new YException($server, YDB_CONFIG_ERROR);
    }
  }

  public static function _dbGetTableList() {
    $tableList = array();
    $sql       = "show tables";
    $q         = self::dbQuery($sql, false);
    while ($d = self::dbFetchArray($q)) {
      $k           = array_key_first($d);
      $tableList[] = $d[$k];
    }
    return $tableList;
  }

  public function lastError() {
    $ret = [
      'code'    => 0,
      'message' => '',
    ];
    if (!empty($this->connection)) {
      $ret['code']    = mysqli_errno($this->connection);
      $ret['message'] = mysqli_error($this->connection);
    }
    return $ret;
  }

  public function mode() {
    $ret = (($this->mode == 0) ? 'ro' : (($this->mode == 1) ? 'rw' : 'undefined'));
    return $ret;
  }

  public function name() {
    return $this->name;
  }
}

class MySQLCollection extends YDBHelper implements YDBCollection {
  function __construct($roLink, $rwLink, $collectionName, $id_name = "id") {
    $this->ro_connection = $roLink;
    $this->rw_connection = $rwLink;
    if ($this->ro_connection || $this->rw_connection) {
      $this->collectionName = preg_replace('/\s+/', ' ', "$collectionName");
      $this->id_name        = preg_replace('/\s+/', ' ', "$id_name");
    } else {
      throw new YException('', YDB_MISSING_CONNECTION);
    }
  }

  private function _query($sql) {
    _log("$sql");
    $ret = mysqli_query($this->ro_connection->connection, $sql);
    return $ret;
  }

  private function _releaseQuery($query) {
    if ($query) {
      if (is_object($query)) {
        mysqli_free_result($query);
      }

    }

  }

  private function _queryAndFetch($sql) {
    $ret = null;
    $q   = $this->_query($sql);
    if ($q) {
      $ret = mysqli_fetch_assoc($q);
      $this->_releaseQuery($q);
    }
    return $ret;
  }

  public function _getCollectionStructure() {
    $tableName = $this->collectionName;

    $fieldList = array();
    $sql       = "show columns from `$tableName`";
    $q         = $this->_query($sql);
    while ($d = mysqli_fetch_assoc($q)) {
      $fieldList[$d['Field']] = array(
        'type'    => $d['Type'],
        'null'    => $d['Null'],
        'key'     => $d['Key'],
        'default' => $d['Default'],
        'extra'   => $d['Extra'],
      );
    }
    return $fieldList;
  }

  public function name() {
    return $this->collectionName;
  }

  public function query($aQuery, $offset = 0, $limit = 100) {
    $ret = [];
    $sql = "select * from " . $this->collectionName . " where $aQuery limit $offset, $limit";
    $q   = $this->_query($sql);
    if ($q) {
      while ($d = mysqli_fetch_assoc($q)) {
        $ret[] = $d;
      }
    } else {
      if (mysqli_errno($this->ro_connection->connection)) {
        $errDetail = $this->ro_connection->lastError();
        throw new YException("mysql " . $errDetail['code'] . ": " . $errDetail['message'] . " doing: " . $sql, YDB_MISSING_DATABASE);
      }
    }

    return $ret;
  }

  public function get($IdOrCondition) {
    $ret           = [];
    $IdOrCondition = preg_replace('/\s+/', ' ', "$IdOrCondition");
    $kind          = $this->kindOfExpression($IdOrCondition);
    if ($kind == 'expr') {
      $ret = $this->query($IdOrCondition, 0, 1);
    } else {
      $key = $IdOrCondition;
      if ($kind != 'int') {
        $key = "'" . $key . "'";
      }
      $ret = $this->query($this->id_name . " = " . $key, 0, 1);
    }

    $ret = _getValue($ret, 0);
    return $ret;
  }

  public function set(&$aData) {

    $cureField = function ($fieldAsChar, $fieldValue) {

      $specs = ["true", "false", "null"];

      if ($fieldAsChar || !is_numeric($fieldValue)) {
        if (null === $fieldValue) {
          $fieldValue = "NULL";
        }
        if (true === $fieldValue) {
          $fieldValue = "TRUE";
        }
        if (false === $fieldValue) {
          $fieldValue = "FALSE";
        }

        if (in_array(mb_strtolower($fieldValue), $specs)) {
          $fieldValue = mb_strtoupper($fieldValue);
        } else {
          $fieldValue = strip_tags(addslashes($fieldValue));
          $fieldValue = "'$fieldValue'";
        }
      }

      return $fieldValue;
    };

    $ret = false;
    if (is_array($aData)) {
      $structure = $this->_getCollectionStructure();

      $id_value  = $cureField(true, _getValue($aData, $this->id_name, md5(gen_uuid)));
      $sqlUpdate = "";
      if (empty($aData[$this->id_name])) {
        // new record
        $this->_releaseQuery($this->_query("insert into {$this->collectionName} ({$this->id_name}) values ($id_value)"));
        $aData[$this->id_name] = $newId;
      } else {
        $cc = $this->_queryAndFetch("select count(*) from {$this->collectionName} where {$this->id_name}={$id_value}");
        if ($cc == 0) {
          $this->_releaseQuery($this->_query("insert into {$this->collectionName} ({$this->id_name}) values ($id_value)"));
        }
      }

      foreach ($aData as $fieldName => $fieldValue) {
        if (!empty($structure[$fieldName])) {
          $fieldType = $structure[$fieldName]['type'];
          if (strpos($fieldType, 'char') !== false) {
            $fieldAsChar = true;
          } else {
            $fieldAsChar = false;
          }

          if ($this->id_name != $fieldName) {
            if ($sqlUpdate > '') {
              $sqlUpdate .= ", ";
            }

            $fieldValue = $cureField($fieldAsChar, $fieldValue);

            $sqlUpdate .= "$fieldName = $fieldValue";
          }
        }
      }

      $sql = "update {$this->collectionName} set $sqlUpdate where {$this->id_name}={$id_value}";
      $q   = $this->_query($sql);
      $ret = (false !== $q);
      $this->_releaseQuery($q);
    }
    return $ret;
  }

  public function delete($IdOrCondition) {

  }

}

DBConnector::registerLink("mysql", "MySQLLink", "MySQLCollection");