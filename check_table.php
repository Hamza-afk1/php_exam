<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Database.php';

// Create a new database connection
$db = new Database();

// Check the structure of the answers table
$query = "DESCRIBE answers";
$stmt = $db->prepare($query);
$db->execute($stmt);
$columns = $db->resultSet($stmt);

// Display the table structure
echo "<h2>Answers Table Structure</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    foreach ($column as $key => $value) {
        echo "<td>" . htmlspecialchars($value) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Display the database schema for adding the missing column if needed
echo "<h2>SQL to Add Missing Column</h2>";
echo "<pre>ALTER TABLE answers ADD COLUMN graded_points INT DEFAULT NULL AFTER is_correct;</pre>";

// Also capture the create method in Answer model
echo "<h2>Answer Create Method</h2>";
try {
    $answerModelPath = __DIR__ . '/models/Answer.php';
    $answerModelContent = file_get_contents($answerModelPath);
    
    // Find the create method
    preg_match('/public function create\([^)]*\)\s*{[^}]*}/s', $answerModelContent, $matches);
    
    if (!empty($matches)) {
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p>Create method not found in Answer.php</p>";
    }
} catch (Exception $e) {
    echo "<p>Error reading Answer.php: " . $e->getMessage() . "</p>";
}
?> 