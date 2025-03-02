<?php
// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

echo "<h1>Adding Passing Score Column</h1>";

try {
    // Create database connection
    $db = new Database();
    
    // Check if the column already exists
    $checkQuery = "SHOW COLUMNS FROM exams LIKE 'passing_score'";
    $stmt = $db->prepare($checkQuery);
    $db->execute($stmt);
    $columnExists = $db->rowCount($stmt) > 0;
    
    if (!$columnExists) {
        // Add the passing_score column to the exams table
        $alterQuery = "ALTER TABLE exams ADD COLUMN passing_score INT NOT NULL DEFAULT 60 COMMENT 'Passing score percentage'";
        $stmt = $db->prepare($alterQuery);
        $db->execute($stmt);
        
        echo "<p class='text-success'>Successfully added 'passing_score' column to exams table!</p>";
        
        // Update existing exams to have a default passing score of 60%
        $updateQuery = "UPDATE exams SET passing_score = 60 WHERE passing_score IS NULL";
        $stmt = $db->prepare($updateQuery);
        $db->execute($stmt);
        
        echo "<p class='text-success'>Updated existing exams with default passing score of 60%.</p>";
    } else {
        echo "<p class='text-info'>The 'passing_score' column already exists in the exams table.</p>";
    }
    
    echo "<p><a href='formateur/exams.php' class='btn btn-primary'>Go to Exams Page</a></p>";
    
} catch (Exception $e) {
    echo "<p class='text-danger'>Error: " . $e->getMessage() . "</p>";
}
?>
