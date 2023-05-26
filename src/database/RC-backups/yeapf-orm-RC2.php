<?php
declare(strict_types = 1);

class ORM {

    private $db;
    private $redis;

    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('localhost', 6379);
        $this->db = new PDO('pgsql:host=localhost;dbname=mydb', 'username', 'password');
    }

    public function find($table, $id) {
        $key = "$table:$id";
        $data = $this->redis->get($key);
        if ($data === false) {
            $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data !== false) {
                $this->redis->set($key, json_encode($data));
            }
        } else {
            $data = json_decode($data, true);
        }
        return $data;
    }

    public function where($table, $params) {
        $where = [];
        $values = [];
        foreach ($params as $column => $value) {
            $where[] = "$column = ?";
            $values[] = $value;
        }
        $where_clause = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT * FROM $table WHERE $where_clause");
        $stmt->execute($values);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function create($table, $params) {
        $columns = [];
        $values = [];
        foreach ($params as $column => $value) {
            $columns[] = $column;
            $values[] = $value;
        }
        $columns_clause = implode(',', $columns);
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $stmt = $this->db->prepare("INSERT INTO $table ($columns_clause) VALUES ($placeholders)");
        $stmt->execute($values);
        $id = $this->db->lastInsertId();
        return $this->find($table, $id);
    }

    public function update($table, $id, $params) {
        $set = [];
        $values = [];
        foreach ($params as $column => $value) {
            $set[] = "$column = ?";
            $values[] = $value;
        }
        $set_clause = implode(',', $set);
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE $table SET $set_clause WHERE id = ?");
        $stmt->execute($values);
        return $this->find($table, $id);
    }

    public function delete($table, $id) {
        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $this->redis->del("$table:$id");
    }

    private function sanitize($value) {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $item) {
                $result[] = $this->sanitize($item);
            }
            return $result;
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function insert($table, $params) {
        $values = $this    ->sanitize(array_values($params));
        return $this->create($table, $values);
    }

    public function select($table, $columns) {
        $stmt = $this->db->prepare("SELECT $columns FROM $table");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

}