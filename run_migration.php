<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Database.php';

// Initialize the database connection
$database = new Database();
$pdo = $database->getConnection();

echo '<h2>Exam Management System - Database Migration</h2>';

// Check if the total_points column exists
try {
    $checkQuery = "SHOW COLUMNS FROM exams LIKE 'total_points'";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // SQL to add total_points column to exams table
        $sql = "ALTER TABLE exams ADD COLUMN total_points INT NOT NULL DEFAULT 20 AFTER passing_score";
        
        // Execute the query
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute();
        
        if ($result) {
            echo '<div style="color: green; padding: 10px; background-color: #e8f5e9; border-radius: 5px; margin-bottom: 10px;">';
            echo '<strong>Success!</strong> Added total_points column to exams table.';
            echo '</div>';
        } else {
            echo '<div style="color: red; padding: 10px; background-color: #ffebee; border-radius: 5px; margin-bottom: 10px;">';
            echo '<strong>Error:</strong> Failed to add column.';
            echo '</div>';
        }
    } else {
        echo '<div style="color: blue; padding: 10px; background-color: #e3f2fd; border-radius: 5px; margin-bottom: 10px;">';
        echo '<strong>Info:</strong> The total_points column already exists in the exams table.';
        echo '</div>';
    }
} catch (PDOException $e) {
    echo '<div style="color: red; padding: 10px; background-color: #ffebee; border-radius: 5px; margin-bottom: 10px;">';
    echo '<strong>Error:</strong> ' . $e->getMessage();
    echo '</div>';
}

// Link to go back to the application
echo '<p><a href="index.php" style="display: inline-block; background-color: #2196f3; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">Return to Application</a></p>';
?> 