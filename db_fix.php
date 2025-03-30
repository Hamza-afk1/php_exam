<?php
// Database configuration - from config.php
$host = 'localhost';
$dbname = 'exam_management';
$username = 'root';
$password = '';

try {
    echo "Database modification script running...\n";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if the column already exists to avoid errors
    $stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'points'");
    $columnExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$columnExists) {
        // Add the missing column
        $pdo->exec("ALTER TABLE questions ADD COLUMN points INT DEFAULT 1");
        echo "Successfully added points column to questions table.\n";
    } else {
        echo "The points column already exists in the questions table.\n";
    }
    
    echo "Database modification completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 