<?php
require_once __DIR__ . '/../config/config.php';

try {
    // First, create the database if it doesn't exist
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'formateur', 'stagiaire') NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            formateur_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            time_limit INT NOT NULL COMMENT 'in minutes',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (formateur_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            question_type ENUM('qcm', 'open') NOT NULL,
            question_text TEXT NOT NULL,
            options JSON NULL COMMENT 'For QCM options',
            correct_answer TEXT NULL COMMENT 'For QCM correct answer',
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            stagiaire_id INT NOT NULL,
            question_id INT NOT NULL,
            answer_text TEXT,
            is_correct BOOLEAN NULL COMMENT 'Auto-generated for QCM',
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            FOREIGN KEY (stagiaire_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            stagiaire_id INT NOT NULL,
            exam_id INT NOT NULL,
            score INT,
            graded_by INT NULL COMMENT 'Formateur ID who graded open questions',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (stagiaire_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
        );
    ");

    // Insert default admin user
    $username = 'admin';
    $email = 'admin@example.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role) 
        VALUES (:username, :email, :password, 'admin')
        ON DUPLICATE KEY UPDATE email = VALUES(email), password = VALUES(password)
    ");
    
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $password
    ]);

    echo "Database setup completed successfully!<br>";
    echo "Default admin credentials:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";

} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "<br>";
}
?> 