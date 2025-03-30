<?php
require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'passing_score'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add passing_score column if it doesn't exist
        $sql = "ALTER TABLE exams ADD COLUMN passing_score INT DEFAULT 70";
        $pdo->exec($sql);
        echo "Successfully added passing_score column to exams table\n";
    } else {
        echo "Column passing_score already exists\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 