<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'passing_score'");
    $passingScoreExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'total_points'");
    $totalPointsExists = $stmt->rowCount() > 0;
    
    // Add columns if they don't exist
    if (!$passingScoreExists) {
        $pdo->exec("ALTER TABLE exams ADD COLUMN passing_score INT DEFAULT 60 COMMENT 'Minimum percentage to pass the exam'");
        echo "Added passing_score column to exams table.<br>";
    } else {
        echo "passing_score column already exists.<br>";
    }
    
    if (!$totalPointsExists) {
        $pdo->exec("ALTER TABLE exams ADD COLUMN total_points INT DEFAULT 20 COMMENT 'Total possible points for the exam'");
        echo "Added total_points column to exams table.<br>";
    } else {
        echo "total_points column already exists.<br>";
    }
    
    echo "<p>Database update completed successfully!</p>";
    echo "<p><a href='formateur/exams.php'>Go to Exams</a></p>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 