<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

// Initialize the database connection
$database = new Database();
$pdo = $database->getConnection();

// SQL to add total_points column to exams table
$sql = "ALTER TABLE exams ADD COLUMN total_points INT NOT NULL DEFAULT 20 AFTER passing_score";

try {
    // Execute the query
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "Successfully added total_points column to exams table!";
    } else {
        echo "Failed to add column.";
    }
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { // Duplicate column error
        echo "The total_points column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
