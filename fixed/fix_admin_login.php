<?php
// This script will fix admin login issues by creating a fresh admin user

// Load configuration
require_once __DIR__ . '/config/config.php';

// Admin credentials
$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';
$role = 'admin';

echo "<h1>Admin Login Fix</h1>";

try {
    // Connect to MySQL directly
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    echo "<p>Connected to database successfully</p>";
    
    // Check if users table exists
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() == 0) {
        echo "<p style='color:red;'>Users table doesn't exist. Creating database schema...</p>";
        
        // Import schema.sql file
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        $queries = explode(';', $schema);
        
        foreach ($queries as $query) {
            if (trim($query) != '') {
                $pdo->exec($query);
            }
        }
        
        echo "<p style='color:green;'>Database schema created successfully</p>";
    }
    
    // Check if admin exists and delete it
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $adminExists = $stmt->fetch();
    
    if ($adminExists) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$adminExists['id']]);
        echo "<p>Removed existing admin user</p>";
    }
    
    // Create a fresh admin user with proper password hash
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$username, $email, $hashedPassword, $role]);
    
    echo "<div style='padding: 20px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px; margin: 20px 0;'>";
    echo "<h2 style='color: #3c763d;'>✅ Admin user created successfully!</h2>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
    echo "</div>";
    
    // Verify the hash
    echo "<h3>Technical verification:</h3>";
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $storedHash = $stmt->fetch(PDO::FETCH_COLUMN);
    
    echo "<p>Stored password hash: " . $storedHash . "</p>";
    echo "<p>Password verification test: " . (password_verify($password, $storedHash) ? 'SUCCESS' : 'FAILED') . "</p>";
    
    // Manual verification SQL to help debug if needed
    echo "<div style='background: #f8f8f8; padding: 10px; border: 1px solid #ddd; margin-top: 20px;'>";
    echo "<p><strong>For manual database verification, run this SQL query:</strong></p>";
    echo "<pre>SELECT id, username, email, password, role FROM users WHERE username = 'admin';</pre>";
    echo "</div>";
    
    echo "<p style='margin-top: 20px;'><a href='login.php' style='padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='padding: 20px; background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 4px; margin: 20px 0;'>";
    echo "<h2 style='color: #a94442;'>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p>The database '".DB_NAME."' doesn't exist. Creating it now...</p>";
        
        try {
            // Connect without database specified
            $pdo = new PDO(
                'mysql:host=' . DB_HOST,
                DB_USER,
                DB_PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            
            // Create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS ".DB_NAME);
            echo "<p style='color:green;'>Database created successfully. Please refresh this page.</p>";
            
            echo "<p><a href='fix_admin_login.php' style='padding: 10px 20px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px;'>Refresh This Page</a></p>";
            
        } catch (PDOException $ex) {
            echo "<p style='color:red;'>Failed to create database: " . $ex->getMessage() . "</p>";
            echo "<p>Make sure your database server is running and that the username and password in config.php are correct.</p>";
        }
    }
}
?>
