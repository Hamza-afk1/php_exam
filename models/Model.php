<?php
require_once __DIR__ . '/../utils/Database.php';

abstract class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAll() {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->db->prepare($query);
        $this->db->execute($stmt);
        return $this->db->resultSet($stmt);
    }
    
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $params = [':id' => $id];
        $this->db->execute($stmt, $params);
        return $this->db->single($stmt);
    }
    
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $params = [':id' => $id];
        return $this->db->execute($stmt, $params);
    }
    
    // Additional methods to be implemented by child classes
    abstract public function create(array $data);
    abstract public function update(array $data, $id);
}
?>
