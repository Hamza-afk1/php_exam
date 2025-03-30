<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/User.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Admin Password Reset</h2>";

try {
    $userModel = new User();
    
    // First find the admin user
    $adminUser = $userModel->findByUsername('admin');
    
    if ($adminUser) {
        // Update the admin user with new password
        $updateData = [
            'username' => 'admin',
            'email' => $adminUser['email'],
            'password' => 'admin123',
            'role' => 'admin'
        ];
        
        if ($userModel->update($updateData, $adminUser['id'])) {
            echo "<div style='color: green; margin: 20px 0;'>";
            echo "Admin password has been reset successfully!<br>";
            echo "New login credentials:<br>";
            echo "Username: admin<br>";
            echo "Password: admin123";
            echo "</div>";
        } else {
            echo "<div style='color: red; margin: 20px 0;'>";
            echo "Failed to reset admin password.";
            echo "</div>";
        }
    } else {
        // If admin doesn't exist, create it
        $adminData = [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'role' => 'admin'
        ];
        
        if ($userModel->create($adminData)) {
            echo "<div style='color: green; margin: 20px 0;'>";
            echo "Admin user created successfully!<br>";
            echo "Login credentials:<br>";
            echo "Username: admin<br>";
            echo "Password: admin123";
            echo "</div>";
        } else {
            echo "<div style='color: red; margin: 20px 0;'>";
            echo "Failed to create admin user.";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='color: red; margin: 20px 0;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='login.php' style='text-decoration: none; padding: 10px 20px; background-color: #007bff; color: white; border-radius: 5px;'>Go to Login</a>";
echo "</div>";
?> 