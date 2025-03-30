<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/User.php';

// Create user model instance
$userModel = new User();

// Check if admin exists
$adminUser = $userModel->findByUsername('admin');

if (!$adminUser) {
    // Create admin user
    $adminData = [
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => 'admin123', // You should change this password
        'role' => 'admin'
    ];
    
    if ($userModel->create($adminData)) {
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<a href='login.php'>Go to Login</a>";
    } else {
        echo "Failed to create admin user.";
    }
} else {
    echo "Admin user already exists.<br>";
    echo "Try logging in with your admin credentials.<br>";
    echo "If you forgot your password, contact the system administrator.<br>";
    echo "<a href='login.php'>Go to Login</a>";
}
?> 