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

// Test admin credentials
$username = 'admin';
$password = 'admin123';

echo "<h1>Admin Access Diagnostic Tool</h1>";

// Style for the page
echo '<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1000px; margin: 0 auto; }
    h1, h2, h3 { color: #333; }
    .card { border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 20px; }
    .card-header { background: #f5f5f5; padding: 10px; margin: -15px -15px 15px; border-bottom: 1px solid #ddd; font-weight: bold; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    code { background: #f8f8f8; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
    .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
</style>';

// Check current session state
echo '<div class="card">
    <div class="card-header">Current Session State</div>';
echo '<p>Session ID: ' . session_id() . '</p>';
echo '<p>Session data:</p>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
if (Session::isLoggedIn()) {
    echo '<p class="success">User is logged in as: ' . Session::get('username') . '</p>';
    echo '<p>User role: ' . Session::get('role') . '</p>';
} else {
    echo '<p class="warning">User is not logged in</p>';
}
echo '</div>';

// Test authentication
echo '<div class="card">
    <div class="card-header">Admin Authentication Test</div>';
echo '<p>Testing admin credentials:</p>';
echo '<ul>';
echo '<li>Username: ' . htmlspecialchars($username) . '</li>';
echo '<li>Password: ' . htmlspecialchars($password) . '</li>';
echo '</ul>';

try {
    $userModel = new User();
    $user = $userModel->authenticate($username, $password);
    
    if ($user) {
        echo '<p class="success">Authentication successful!</p>';
        echo '<p>User data:</p>';
        echo '<pre>';
        echo "ID: {$user['id']}\n";
        echo "Username: {$user['username']}\n";
        echo "Email: {$user['email']}\n";
        echo "Role: {$user['role']}\n";
        echo '</pre>';
    } else {
        echo '<p class="error">Authentication failed!</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">Error during authentication: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</div>';

// Database configuration
echo '<div class="card">
    <div class="card-header">Database Configuration</div>';
echo '<p>Current database settings:</p>';
echo '<ul>';
echo '<li>Host: ' . DB_HOST . '</li>';
echo '<li>Database: ' . DB_NAME . '</li>';
echo '<li>User: ' . DB_USER . '</li>';
echo '<li>Password: ' . (empty(DB_PASS) ? '[empty]' : '[set]') . '</li>';
echo '</ul>';
echo '</div>';

// Session handling check
echo '<div class="card">
    <div class="card-header">Session Handling</div>';
echo '<p>Session configuration:</p>';
echo '<ul>';
echo '<li>Session name: ' . SESSION_NAME . '</li>';
echo '<li>Session cookie httponly: ' . ini_get('session.cookie_httponly') . '</li>';
echo '<li>Session use only cookies: ' . ini_get('session.use_only_cookies') . '</li>';
echo '</ul>';

// Check if Session methods are working
echo '<p>Testing Session class methods:</p>';
echo '<ul>';
echo '<li>Session::init(): ' . (function_exists('session_start') ? '<span class="success">Available</span>' : '<span class="error">Not available</span>') . '</li>';
echo '<li>Session::isLoggedIn(): Returns ' . (Session::isLoggedIn() ? 'true' : 'false') . '</li>';
echo '</ul>';
echo '</div>';

// Set admin session manually
echo '<div class="card">
    <div class="card-header">Set Admin Session Manually</div>';
echo '<p>Click the button below to manually set the admin session and then try accessing the admin dashboard:</p>';
if ($user) {
    echo '<form method="post">';
    echo '<input type="hidden" name="set_admin_session" value="1">';
    echo '<button type="submit" class="btn">Set Admin Session</button>';
    echo '</form>';
    
    // Process form submission
    if (isset($_POST['set_admin_session'])) {
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('role', $user['role']);
        
        echo '<p class="success">Admin session set successfully!</p>';
        echo '<p>Session data updated:</p>';
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';
        
        echo '<p><a href="admin/index.php" class="btn">Go to Admin Dashboard</a></p>';
    }
} else {
    echo '<p class="error">Cannot set admin session because authentication failed.</p>';
}
echo '</div>';

// Admin folder check
echo '<div class="card">
    <div class="card-header">Admin Folder Check</div>';
$adminFolderPath = __DIR__ . '/admin';
$adminIndexPath = $adminFolderPath . '/index.php';
$adminHeaderPath = $adminFolderPath . '/includes/header.php';

if (file_exists($adminFolderPath)) {
    echo '<p class="success">Admin folder exists: ' . $adminFolderPath . '</p>';
    
    if (file_exists($adminIndexPath)) {
        echo '<p class="success">Admin index.php exists</p>';
    } else {
        echo '<p class="error">Admin index.php does not exist!</p>';
    }
    
    if (file_exists($adminHeaderPath)) {
        echo '<p class="success">Admin header.php exists</p>';
    } else {
        echo '<p class="error">Admin header.php does not exist!</p>';
    }
} else {
    echo '<p class="error">Admin folder does not exist!</p>';
}
echo '</div>';

// Troubleshooting steps
echo '<div class="card">
    <div class="card-header">Troubleshooting Steps</div>';
echo '<ol>';
echo '<li>Clear your browser cookies for localhost.</li>';
echo '<li>Run <a href="setup_admin.php">setup_admin.php</a> to ensure the admin user exists.</li>';
echo '<li>Set the admin session manually using the button above.</li>';
echo '<li>Check that all files in the admin folder exist and have the correct permissions.</li>';
echo '<li>Ensure your database connection is working properly.</li>';
echo '</ol>';
echo '</div>';
?> 