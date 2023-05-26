<?php
declare(strict_types = 1);

class ORM {
  private $conn;
  private $table;

  public function __construct(PDO $conn, string $table) {
    $this->conn = $conn;
    $this->table = $table;
  }

  public function find(int $id) {
    $stmt = $this->conn->prepare("SELECT * FROM $this->table WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
      return null;
    }
    return $result;
  }

  public function findAll() {
    $stmt = $this->conn->prepare("SELECT * FROM $this->table");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$result) {
      return null;
    }
    return $result;
  }

  public function save(array $data) {
    $columns = implode(', ', array_keys($data));
    $values = implode(', :', array_keys($data));
    $sql = "INSERT INTO $this->table ($columns) VALUES (:$values)";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($data);
    return $this->conn->lastInsertId();
  }

  public function update(int $id, array $data) {
    $fields = '';
    foreach ($data as $key => $value) {
      $fields .= "$key = :$key, ";
    }
    $fields = rtrim($fields, ', ');
    $sql = "UPDATE $this->table SET $fields WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $data['id'] = $id;
    $stmt->execute($data);
    return $stmt->rowCount();
  }

  public function delete(int $id) {
    $stmt = $this->conn->prepare("DELETE FROM $this->table WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount();
  }
}
