<?php
/**
 * Database Class
 * Handles database connection and operations
 */

// Check if the class already exists to prevent redeclaration errors
if (!class_exists('Database')) {
    
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;
    private $error;
    
    /**
     * Constructor - Creates a new PDO connection
     */
    public function __construct() {
        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Create a new PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
            error_log('Database connection established successfully');
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log('Database Connection Error: ' . $this->error);
            throw new Exception('Database connection failed: ' . $this->error);
        }
    }
    
    /**
     * Prepare statement with query
     * 
     * @param string $query - The SQL query to prepare
     * @return PDOStatement The prepared statement
     */
    public function prepare($query) {
        return $this->dbh->prepare($query);
    }
    
    /**
     * Execute prepared statement with values
     * 
     * @param PDOStatement $statement - The prepared statement
     * @param array $params - Array of parameter values
     * @return PDOStatement The executed statement
     */
    public function execute($statement, $params = []) {
        try {
            return $statement->execute($params);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log('Database Execute Error: ' . $this->error);
            throw new Exception('Database query execution failed: ' . $this->error);
        }
    }
    
    /**
     * Get single record as associative array
     * 
     * @param PDOStatement $statement - The executed statement
     * @return array The single record
     */
    public function single($statement) {
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get result set as array of associative arrays
     * 
     * @param PDOStatement $statement - The executed statement
     * @return array The result set
     */
    public function resultSet($statement) {
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get row count
     * 
     * @param PDOStatement $statement - The executed statement
     * @return int The row count
     */
    public function rowCount($statement) {
        return $statement->rowCount();
    }
    
    /**
     * Get last insert ID
     * 
     * @return string The last insert ID
     */
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->dbh->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollBack() {
        return $this->dbh->rollBack();
    }
    
    /**
     * Returns the error info
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Execute a quick query and return result (for simple queries)
     * 
     * @param string $query - The SQL query
     * @param array $params - Array of parameter values
     * @return array The result set
     */
    public function query($query, $params = []) {
        $stmt = $this->prepare($query);
        $this->execute($stmt, $params);
        return $this->resultSet($stmt);
    }
    
    /**
     * Execute a quick query and return a single record (for simple queries)
     * 
     * @param string $query - The SQL query
     * @param array $params - Array of parameter values
     * @return array The single record
     */
    public function querySingle($query, $params = []) {
        $stmt = $this->prepare($query);
        $this->execute($stmt, $params);
        return $this->single($stmt);
    }
}

} // End of class_exists check 