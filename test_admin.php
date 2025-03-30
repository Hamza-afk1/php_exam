<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and dependencies
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/models/User.php';

// Create a database instance
$db = new Database();

echo "<h2>Database Connection Test</h2>";
try {
    // Test the database connection
    $db->connect();
    echo "<p style='color:green'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check for admin user
echo "<h2>Admin User Check</h2>";
$userModel = new User();
$adminUser = $userModel->findByUsername('admin');

if ($adminUser) {
    echo "<p style='color:green'>✓ Admin user exists</p>";
    echo "<pre>";
    // Show admin user details without the password hash
    $adminDetails = $adminUser;
    $adminDetails['password'] = '[HIDDEN]';
    print_r($adminDetails);
    echo "</pre>";
    
    // Test password
    $testPassword = 'admin123';
    if (password_verify($testPassword, $adminUser['password'])) {
        echo "<p style='color:green'>✓ Password 'admin123' is correct for admin user</p>";
    } else {
        echo "<p style='color:red'>✗ Password 'admin123' is NOT correct for admin user</p>";
    }
} else {
    echo "<p style='color:red'>✗ Admin user does not exist</p>";
}

// Check session handling
echo "<h2>Session Configuration Check</h2>";
echo "<p>Session name: " . SESSION_NAME . "</p>";
echo "<p>Base URL: " . BASE_URL . "</p>";

// Check redirect paths
echo "<h2>File Path Check</h2>";
$adminIndexPath = __DIR__ . '/admin/index.php';
if (file_exists($adminIndexPath)) {
    echo "<p style='color:green'>✓ Admin index file exists at: " . $adminIndexPath . "</p>";
} else {
    echo "<p style='color:red'>✗ Admin index file does NOT exist at: " . $adminIndexPath . "</p>";
}

// Check all model files
echo "<h2>Model Files Check</h2>";
$modelFiles = ['User.php', 'Exam.php', 'Result.php'];
foreach ($modelFiles as $file) {
    $path = __DIR__ . '/models/' . $file;
    if (file_exists($path)) {
        echo "<p style='color:green'>✓ Model file exists: " . $file . "</p>";
    } else {
        echo "<p style='color:red'>✗ Model file does NOT exist: " . $file . "</p>";
    }
}

// Done
echo "<h2>Test Complete</h2>";
echo "<p>Check the results above to diagnose any issues with your admin login.</p>";
?> 