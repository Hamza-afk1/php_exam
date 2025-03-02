<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $error;
    
    public function __construct() {
        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Create a new PDO instance
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Connection Error: ' . $this->error;
        }
    }
    
    // Get connection
    public function getConnection() {
        return $this->conn;
    }
    
    // Prepare statement with query
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    // Execute statement
    public function execute($stmt, $params = []) {
        return $stmt->execute($params);
    }
    
    // Get row count
    public function rowCount($stmt) {
        return $stmt->rowCount();
    }
    
    // Get single record as object
    public function single($stmt) {
        $this->execute($stmt);
        return $stmt->fetch();
    }
    
    // Get record set as array of objects
    public function resultSet($stmt) {
        $this->execute($stmt);
        return $stmt->fetchAll();
    }
    
    // Get last id inserted
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>
