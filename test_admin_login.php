<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and dependencies
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/utils/Session.php';

// Start fresh session
Session::destroy();
Session::init();

// Basic styling for the page
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Login Test</title>
    <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { margin-bottom: 20px; }
        .step { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .details { margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 3px; }
        pre { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Admin Login Test</h1>";

// STEP 1: Test Database Connection
echo "<div class='step'>
        <h3>Step 1: Database Connection</h3>";
try {
    $db = new Database();
    $db->connect();
    echo "<p class='success'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<div class='details'>Please check your database configuration in config.php</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// STEP 2: Verify Admin User Exists
echo "<div class='step'>
        <h3>Step 2: Admin User Verification</h3>";
$userModel = new User();
$adminUser = $userModel->findByUsername('admin');

if ($adminUser) {
    echo "<p class='success'>✓ Admin user exists</p>";
    echo "<div class='details'>";
    echo "<p>User ID: " . $adminUser['id'] . "</p>";
    echo "<p>Username: " . $adminUser['username'] . "</p>";
    echo "<p>Email: " . $adminUser['email'] . "</p>";
    echo "<p>Role: " . $adminUser['role'] . "</p>";
    echo "</div>";
} else {
    echo "<p class='error'>✗ Admin user not found</p>";
    echo "<div class='details'>Please run the fix_admin_login.php script to create the admin user</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// STEP 3: Test Password Authentication
echo "<div class='step'>
        <h3>Step 3: Password Verification</h3>";
$isValidPassword = password_verify('admin123', $adminUser['password']);
if ($isValidPassword) {
    echo "<p class='success'>✓ Password 'admin123' is valid for admin user</p>";
} else {
    echo "<p class='error'>✗ Password 'admin123' is NOT valid</p>";
    echo "<div class='details'>Please run the fix_admin_login.php script to reset the admin password</div>";
}
echo "</div>";

// STEP 4: Test Authentication Method
echo "<div class='step'>
        <h3>Step 4: Authentication Method</h3>";
$authenticatedUser = $userModel->authenticate('admin', 'admin123');
if ($authenticatedUser) {
    echo "<p class='success'>✓ User::authenticate() method successful</p>";
} else {
    echo "<p class='error'>✗ User::authenticate() method failed</p>";
    echo "<div class='details'>There may be an issue with the authentication method in User.php</div>";
    
    // Debug User::authenticate method
    echo "<pre>";
    echo "Debug Output:\n";
    echo "Verifying password: " . (password_verify('admin123', $adminUser['password']) ? 'Yes' : 'No') . "\n";
    echo "Admin user exists: " . ($adminUser ? 'Yes' : 'No') . "\n";
    echo "</pre>";
}
echo "</div>";

// STEP 5: Test Session Setting
echo "<div class='step'>
        <h3>Step 5: Session Variable Setting</h3>";
if ($authenticatedUser) {
    // Set session variables manually
    Session::set('user_id', $authenticatedUser['id']);
    Session::set('username', $authenticatedUser['username']);
    Session::set('role', $authenticatedUser['role']);
    
    // Verify session variables
    $sessionUserId = Session::get('user_id');
    $sessionUsername = Session::get('username');
    $sessionRole = Session::get('role');
    
    if ($sessionUserId && $sessionUsername && $sessionRole) {
        echo "<p class='success'>✓ Session variables set successfully</p>";
        echo "<div class='details'>";
        echo "<p>user_id: " . $sessionUserId . "</p>";
        echo "<p>username: " . $sessionUsername . "</p>";
        echo "<p>role: " . $sessionRole . "</p>";
        echo "</div>";
    } else {
        echo "<p class='error'>✗ Session variables not set correctly</p>";
        echo "<div class='details'>";
        echo "<p>user_id: " . ($sessionUserId ? $sessionUserId : 'NOT SET') . "</p>";
        echo "<p>username: " . ($sessionUsername ? $sessionUsername : 'NOT SET') . "</p>";
        echo "<p>role: " . ($sessionRole ? $sessionRole : 'NOT SET') . "</p>";
        echo "</div>";
    }
} else {
    echo "<p class='error'>✗ Cannot test session - authentication failed</p>";
}
echo "</div>";

// STEP 6: Test isLoggedIn and Role Check Methods
echo "<div class='step'>
        <h3>Step 6: Session Helper Methods</h3>";
if (Session::isLoggedIn()) {
    echo "<p class='success'>✓ Session::isLoggedIn() returns true</p>";
} else {
    echo "<p class='error'>✗ Session::isLoggedIn() returns false</p>";
    echo "<div class='details'>Check implementation of Session::isLoggedIn() method</div>";
}

try {
    // Get all session variables for debugging
    echo "<div class='details'>";
    echo "<p>All session variables:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "</div>";
} catch (Exception $e) {
    echo "<p class='error'>Error getting session: " . $e->getMessage() . "</p>";
}
echo "</div>";

// STEP 7: Test Login Redirect Logic
echo "<div class='step'>
        <h3>Step 7: Login Redirect Logic</h3>";
$role = Session::get('role');
if ($role) {
    echo "<p>Based on your role ($role), you should be redirected to:</p>";
    if ($role === 'admin') {
        echo "<p class='success'>✓ " . BASE_URL . "/admin/index.php</p>";
    } elseif ($role === 'formateur') {
        echo "<p class='success'>✓ " . BASE_URL . "/formateur/index.php</p>";
    } else {
        echo "<p class='success'>✓ " . BASE_URL . "/stagiaire/index.php</p>";
    }
} else {
    echo "<p class='error'>✗ Role not set in session</p>";
}
echo "</div>";

// STEP 8: Test Path to Admin Dashboard
echo "<div class='step'>
        <h3>Step 8: Admin Dashboard File Check</h3>";
$adminDashPath = __DIR__ . '/admin/index.php';
if (file_exists($adminDashPath)) {
    echo "<p class='success'>✓ Admin dashboard file exists</p>";
} else {
    echo "<p class='error'>✗ Admin dashboard file does not exist at: $adminDashPath</p>";
}
echo "</div>";

// Add button to try actual login
echo "<div style='margin: 30px 0; text-align: center;'>";
echo "<a href='" . BASE_URL . "/fix_admin_login.php' class='btn btn-warning mr-2'>Run Admin Login Fix</a>";
echo "<a href='" . BASE_URL . "/login.php' class='btn btn-success ml-2'>Try Actual Login</a>";
echo "</div>";

// Final instructions
echo "<div class='step'>
        <h3>Next Steps</h3>
        <ol>
            <li>If all checks passed, try logging in with username <strong>admin</strong> and password <strong>admin123</strong></li>
            <li>If the problem persists, check your server error logs for additional information</li>
            <li>Make sure your database is properly configured in config.php</li>
            <li>Verify that the .htaccess file is not causing redirects</li>
        </ol>
      </div>";

echo "</div></body></html>";
?> 