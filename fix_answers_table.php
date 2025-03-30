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

// First check if the column already exists
$query = "SHOW COLUMNS FROM answers LIKE 'graded_points'";
$stmt = $db->prepare($query);
$db->execute($stmt);
$columnExists = count($db->resultSet($stmt)) > 0;

if (!$columnExists) {
    // Add the missing graded_points column
    $query = "ALTER TABLE answers ADD COLUMN graded_points INT DEFAULT NULL AFTER is_correct";
    $stmt = $db->prepare($query);
    $result = $db->execute($stmt);
    
    if ($result) {
        echo "<h2>Success!</h2>";
        echo "<p>The 'graded_points' column has been added to the answers table.</p>";
    } else {
        echo "<h2>Error</h2>";
        echo "<p>Failed to add the 'graded_points' column to the answers table.</p>";
    }
} else {
    echo "<h2>Column Already Exists</h2>";
    echo "<p>The 'graded_points' column already exists in the answers table.</p>";
}

// For review, show the current structure of the answers table
$query = "DESCRIBE answers";
$stmt = $db->prepare($query);
$db->execute($stmt);
$columns = $db->resultSet($stmt);

// Display the table structure
echo "<h2>Current Answers Table Structure</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    foreach ($column as $key => $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='formateur/questions.php'>Return to Questions Page</a></p>";
?> 