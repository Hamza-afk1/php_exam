<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/User.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create user model instance
$userModel = new User();

// Admin credentials
$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';

// Check if admin exists
$adminUser = $userModel->findByUsername($username);

if (!$adminUser) {
    // Create admin user
    $adminData = [
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'role' => 'admin'
    ];
    
    if ($userModel->create($adminData)) {
        echo "<div style='max-width: 500px; margin: 50px auto; padding: 20px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
        echo "<h2 style='color: #3c763d; margin-top: 0;'>✅ Admin User Created Successfully!</h2>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        echo "<p style='margin-top: 20px;'><a href='login.php' style='padding: 10px 20px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px;'>Go to Login Page</a></p>";
        echo "</div>";
    } else {
        echo "<div style='max-width: 500px; margin: 50px auto; padding: 20px; background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 4px;'>";
        echo "<h2 style='color: #a94442; margin-top: 0;'>❌ Failed to Create Admin User</h2>";
        echo "<p>There was an error creating the admin user. Please check your database configuration.</p>";
        echo "</div>";
    }
} else {
    // Reset admin password
    $adminData = [
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'role' => 'admin'
    ];
    
    if ($userModel->update($adminData, $adminUser['id'])) {
        echo "<div style='max-width: 500px; margin: 50px auto; padding: 20px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
        echo "<h2 style='color: #3c763d; margin-top: 0;'>✅ Admin Password Reset Successfully!</h2>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        echo "<p style='margin-top: 20px;'><a href='login.php' style='padding: 10px 20px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px;'>Go to Login Page</a></p>";
        echo "</div>";
    } else {
        echo "<div style='max-width: 500px; margin: 50px auto; padding: 20px; background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 4px;'>";
        echo "<h2 style='color: #a94442; margin-top: 0;'>❌ Failed to Reset Admin Password</h2>";
        echo "<p>There was an error resetting the admin password. Please check your database configuration.</p>";
        echo "</div>";
    }
}
?> 