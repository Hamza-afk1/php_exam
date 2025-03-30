<?php
// Database configuration - from config.php
$host = 'localhost';
$dbname = 'exam_management';
$username = 'root';
$password = '';

try {
    echo "Database table fix script running...\n";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check the structure of the answers table
    $stmt = $pdo->query("DESCRIBE answers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns in answers table: " . implode(", ", $columns) . "\n";
    
    // Check and add required columns if they don't exist
    $requiredColumns = [
        'id' => "ALTER TABLE answers ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY",
        'question_id' => "ALTER TABLE answers ADD COLUMN question_id INT NOT NULL",
        'answer_text' => "ALTER TABLE answers ADD COLUMN answer_text TEXT",
        'is_correct' => "ALTER TABLE answers ADD COLUMN is_correct TINYINT DEFAULT 0"
    ];
    
    foreach ($requiredColumns as $column => $alterSql) {
        if (!in_array($column, $columns)) {
            echo "Adding missing column: $column\n";
            $pdo->exec($alterSql);
        }
    }
    
    // Check if there's an index on question_id
    $stmt = $pdo->query("SHOW INDEX FROM answers WHERE Key_name = 'question_id_index'");
    $index = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$index) {
        echo "Adding index on question_id column\n";
        $pdo->exec("CREATE INDEX question_id_index ON answers(question_id)");
    }
    
    echo "Table fix completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    
    // If the table doesn't exist, create it
    if ($e->getCode() == '42S02') {
        echo "Table 'answers' does not exist. Creating it...\n";
        
        $createTableSql = "CREATE TABLE answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_id INT NOT NULL,
            answer_text TEXT,
            is_correct TINYINT DEFAULT 0,
            INDEX question_id_index (question_id)
        )";
        
        try {
            $pdo->exec($createTableSql);
            echo "Table 'answers' created successfully!\n";
        } catch (PDOException $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 