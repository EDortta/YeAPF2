<?php

(@include_once (__DIR__ . "/ydb_skeleton.php")) || die("Error loading ydb_skeleton.php");
class MySQLLink implements YDBLink {
  /**
   * server is formed as follows:
   *   host:user@database/password
   **/
  function __construct($server, $mode = "rw") {
    $mode = trim(mb_strtolower($mode));

    preg_match('/(.*):(.*)@(.*)\/(.*)$/i', $server, $server_config);
    $this->connection    = null;
    $this->driver        = 'mysql';
    $this->driver_flavor = 'mysqli';
    $this->mode          = ($mode == "ro" ? 0 : ($mode == "rw" ? 1 : -1));

    _log("Configuring mysql link");

    if ($server_config) {
      if ($server_config) {
        $host       = $server_config[1];
        $user       = $server_config[2];
        $database   = $server_config[3];
        $password   = $server_config[4];
        $this->name = $database;
        if (in_array($this->mode, [0, 1])) {
          if (function_exists("mysqli_connect")) {
            _log("Connecting to $server ( $database at $host )");
            $this->connection = mysqli_connect($host, $user, $password, $database);
            if ($this->connection) {
              _log("DB connection ready");
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

class MySQLCollection  extends YDBHelper  implements YDBCollection {
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

  public function name() {
    return $this->collectionName;
  }

  public function query($aQuery, $offset = 0, $limit = 100) {
    $ret = [];
    $sql = "select * from ".$this->collectionName." where $aQuery";
    $q = mysqli_query($this->ro_connection->connection, $sql);
    if ($q) {
      while ($d=mysqli_fetch_array($q)) {
        $ret[]=$d;
      }
    } else {
      if (mysqli_errno($this->ro_connection->connection)) {
        $errDetail = $this->ro_connection->lastError();
        throw new YException("mysql ".$errDetail['code'].": ".$errDetail['message']." doing: ".$sql, YDB_MISSING_DATABASE);
      }
    }
    return $ret;
  }

  public function get($IdOrCondition) {
    $ret = [];
    $IdOrCondition = preg_replace('/\s+/', ' ', "$IdOrCondition");
    $kind = $this->kindOfExpression($IdOrCondition);
    if ($kind=='expr') {
      $ret = $this->query($IdOrCondition, 0, 1);
    } else {
      $key=$IdOrCondition;
      if ($kind!='int') {
        $key="'".$key."'";
      } 
      $ret = $this->query($this->id_name." = ".$key,0,1);
    }

    $ret = _getValue($ret, 0);
    return $ret;
  }

  public function set($aData) {

  }

  public function delete($IdOrCondition) {

  }

}

DBConnector::registerLink("mysql", "MySQLLink", "MySQLCollection");