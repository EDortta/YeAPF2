<?php

(@include_once(__DIR__."/ydb_skeleton.php")) || die("Error loading ydb_skeleton.php");
class MongoDbLink implements YDBLink {
  function __construct($server, $mode='rw') {
    $this->mongoDbServerLocation = $server;
    $this->connection  = new \MongoDB\Driver\Manager($this->mongoDbServerLocation);
  }
}

class MongoCollection implements YDBCollection {
  function __construct($YDBConnection, $collectionName) {
    $this->connection = $YDBConnection->connection;
    $this->collection = $collection;
  }

  public function query($aQuery, $offset=0, $limit=100) {
    $ret   = array();
    $query = new MongoDB\Driver\Query($aQuery);
    $rows  = $this->connection->executeQuery($this->collection, $query);
    foreach ($rows as $key => $value) {
      $ret[$key] = $value;
    }
    return $ret;
  }

  public function insert($aData) {
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert($aData);
    $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
    $result       = $this->connection->executeBulkWrite($this->collection, $bulk, $writeConcern);
    return $result;
  }

  public function update($aCondition, $aData) {
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update($aCondition, ['$set' => $aData]);
    $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
    $result       = $this->connection->executeBulkWrite($this->collection, $bulk, $writeConcern);
    return $result;
  }

  public function delete($aCondition) {

  }

}

/**
 *
 */
class IdentidadesDB extends MongoCollection {

  function __construct($YDBConnection, $collection) {
    parent::__construct( $YDBConnection, $collection );
  }

  public function getInfoByEmail($email) {
    $ret = $this->query(["email" => $email]);
    return $ret;
  }

  public function getInfoByU($u) {
    $ret = $this->query(["u" => $u]);
    return $ret;
  }

  public function getInfoByCPF($cpf) {
    $cpf = soNumeros($cpf);
    $ret = $this->query(["cpf" => $cpf]);
    return $ret;
  }

  public function getInfoByCNPJ($cnpj) {
    $cnpj = soNumeros($cnpj);
    $ret  = $this->query(["cnpj" => $cnpj]);
    return $ret;
  }

  public function createIdentity($aInfo) {
    $ret = false;

    if ((isset($aInfo['cpf'])) && (CPFCorreto($aInfo['cpf']))) {
      $aInfo['cpf'] = soNumeros($aInfo['cpf']);
      if (filter_var($aInfo['email'], FILTER_VALIDATE_EMAIL)) {
        if ($this->getInfoByEmail($aInfo['email']) == null) {
          if ($this->getInfoByCPF($aInfo['cpf']) == null) {
            $ret = $this->insert($aInfo);

          }
        }
      }
    }

    return $ret;
  }
}

global $idDB;
$idDB = new IdentidadesDB("mongodb://192.168.11.67:27017", "NConsLiteId.identidades");

?>