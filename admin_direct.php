<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';
require_once __DIR__ . '/models/User.php';

// Initialize session
Session::init();

// Set admin session variables directly
$username = 'admin';
$userModel = new User();
$user = $userModel->findByUsername($username);

if ($user) {
    // Set session variables
    Session::set('user_id', $user['id']);
    Session::set('username', $user['username']);
    Session::set('role', $user['role']);
    
    echo "<h1>Admin Session Set</h1>";
    echo "<p>The following session variables have been set:</p>";
    echo "<pre>";
    echo "user_id: " . Session::get('user_id') . "\n";
    echo "username: " . Session::get('username') . "\n";
    echo "role: " . Session::get('role') . "\n";
    echo "</pre>";
    
    echo "<p>Click the link below to access the admin dashboard:</p>";
    echo "<p><a href='" . BASE_URL . "/admin/index.php' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Dashboard</a></p>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>Admin user not found in the database. Please <a href='setup_admin.php'>run the setup script</a> first.</p>";
}
?> 