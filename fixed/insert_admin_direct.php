<?php
// Load configuration
require_once __DIR__ . '/config/config.php';

// Admin user credentials - you can change these if desired
$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Connect to MySQL
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    echo "<h1>Direct Admin User Insertion</h1>";
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing user's password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ? OR email = ?");
        $stmt->execute([$hashedPassword, $username, $email]);
        
        echo "<p style='color:green;'>✅ Admin user already exists. Password updated successfully!</p>";
    } else {
        // Insert new admin user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        echo "<p style='color:green;'>✅ New admin user created successfully!</p>";
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h3>Login Credentials:</h3>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
    echo "</div>";
    
    echo "<p><a href='login.php' style='padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none;'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p style='color:red;'>❌ Database '" . DB_NAME . "' does not exist. Please run setup_database.php first.</p>";
        echo "<p><a href='setup_database.php' style='padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none;'>Run Database Setup</a></p>";
    } else if (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "<p style='color:red;'>❌ Database tables don't exist. Please run setup_database.php first.</p>";
        echo "<p><a href='setup_database.php' style='padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none;'>Run Database Setup</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Database error: " . $e->getMessage() . "</p>";
    }
}
?>
