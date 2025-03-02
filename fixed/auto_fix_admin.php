<?php
// Automatic admin user fix script - runs immediately when accessed

// Load configuration
require_once __DIR__ . '/config/config.php';

// Admin credentials
$username = 'admin';
$password = 'admin123';
$email = 'admin@gmail.com'; // Using the same email that was found in the database

// Connect to MySQL
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Generate proper password hash
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update the admin user password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashedPassword, $username]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h1 style='color:green;'>✅ Admin password has been fixed!</h1>";
        echo "<p>The admin user's password has been properly hashed.</p>";
    } else {
        echo "<h1 style='color:orange;'>⚠️ No changes made</h1>";
        echo "<p>The admin user wasn't found or the password was already updated.</p>";
    }
    
    echo "<script>
        // Automatically redirect to login page after 3 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>";
    
    echo "<p>You will be redirected to the login page in 3 seconds...</p>";
    echo "<p>Or <a href='login.php'>click here</a> to go to the login page now.</p>";
    
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h3>Login with these credentials:</h3>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red;'>❌ Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
