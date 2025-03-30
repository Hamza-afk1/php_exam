<?php
/**
 * LEGACY DATABASE CLASS - Use utils/Database.php instead for new code
 */

// Only define this class if it doesn't already exist
if (!class_exists('LegacyDatabase')) {

class LegacyDatabase {
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
            error_log("Attempting database connection to: " . $this->host);
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
            error_log("Database connection successful");
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database connection error: " . $this->error);
            echo 'Connection Error: ' . $this->error;
        }
    }
    
    // Get connection
    public function getConnection() {
        return $this->conn;
    }
    
    // Prepare statement with query
    public function prepare($sql) {
        try {
            error_log("Preparing SQL: " . $sql);
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                error_log("SQL preparation successful");
            } else {
                error_log("SQL preparation failed");
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error preparing SQL: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Execute statement
    public function execute($stmt, $params = []) {
        try {
            error_log("Executing statement with params: " . json_encode($params));
            $result = $stmt->execute($params);
            if ($result) {
                error_log("Statement execution successful");
            } else {
                error_log("Statement execution failed");
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error executing statement: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Get row count
    public function rowCount($stmt) {
        return $stmt->rowCount();
    }
    
    // Get single record as object
    public function single($stmt, $params = []) {
        try {
            if (!empty($params)) {
                $this->execute($stmt, $params);
            }
            $result = $stmt->fetch();
            error_log("Fetched single result: " . ($result ? "Found" : "Not found"));
            return $result;
        } catch (PDOException $e) {
            error_log("Error fetching single result: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Get record set as array of objects
    public function resultSet($stmt, $params = []) {
        try {
            if (!empty($params)) {
                $this->execute($stmt, $params);
            }
            $results = $stmt->fetchAll();
            error_log("Fetched result set. Count: " . count($results));
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching result set: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Get last id inserted
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}

// For backward compatibility, make Database an alias of LegacyDatabase if the Database class doesn't exist yet
if (!class_exists('Database')) {
    class_alias('LegacyDatabase', 'Database');
}

} // End class_exists check
?>
