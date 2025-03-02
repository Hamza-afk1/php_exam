<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

// Initialize the database connection
$database = new Database();
$pdo = $database->getConnection();

// SQL statements to add new fields
$sql = [
    // Add points field to questions table
    "ALTER TABLE questions ADD COLUMN points INT DEFAULT 1",
    
    // Add graded_points and max_points fields to answers table
    "ALTER TABLE answers ADD COLUMN graded_points INT NULL",
    "ALTER TABLE answers ADD COLUMN max_points INT DEFAULT 1",
    
    // Add total_score field to results table
    "ALTER TABLE results ADD COLUMN total_score INT NULL"
];

$errors = [];
$success = [];

// Execute each query
foreach ($sql as $query) {
    try {
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute();
        if ($result) {
            $success[] = "Success: " . $query;
        } else {
            $errors[] = "Failed: " . $query;
        }
    } catch (PDOException $e) {
        // Skip duplicate column errors
        if ($e->getCode() != '42S21') {
            $errors[] = "Error: " . $e->getMessage() . " in query: " . $query;
        } else {
            $success[] = "Column already exists for: " . $query;
        }
    }
}

// Display results
echo "<h2>Database Update Results</h2>";

if (!empty($success)) {
    echo "<h3>Successful Operations:</h3>";
    echo "<ul>";
    foreach ($success as $msg) {
        echo "<li>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3>Errors:</h3>";
    echo "<ul>";
    foreach ($errors as $msg) {
        echo "<li>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

echo "<p><a href='" . BASE_URL . "/formateur/dashboard.php'>Return to Dashboard</a></p>";
?>
