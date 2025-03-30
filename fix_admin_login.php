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

// Create a database instance
$db = new Database();

echo "<h1>Admin Login Fix</h1>";
echo "<p>This script will diagnose and fix issues with the admin login.</p>";

// Start or initialize the session
Session::init();

// 1. Test database connection
echo "<h2>1. Database Connection</h2>";
try {
    $db->connect();
    echo "<p style='color:green'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
    exit;
}

// 2. Create or update admin user
echo "<h2>2. Admin User Setup</h2>";
$userModel = new User();
$adminUser = $userModel->findByUsername('admin');

// Admin user credentials
$adminCredentials = [
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password' => 'admin123',
    'role' => 'admin'
];

if ($adminUser) {
    echo "<p>Admin user already exists.</p>";
    
    // Check if password needs to be reset
    $testPassword = 'admin123';
    if (!password_verify($testPassword, $adminUser['password'])) {
        echo "<p>Resetting admin password...</p>";
        
        $updateData = [
            'username' => 'admin',
            'email' => $adminUser['email'],
            'password' => 'admin123',
            'role' => 'admin'
        ];
        
        if ($userModel->update($updateData, $adminUser['id'])) {
            echo "<p style='color:green'>✓ Admin password reset successfully</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to reset admin password</p>";
        }
    } else {
        echo "<p style='color:green'>✓ Admin password is already correct</p>";
    }
    
    // Ensure role is set to 'admin'
    if ($adminUser['role'] !== 'admin') {
        echo "<p>Fixing admin role...</p>";
        
        $updateData = [
            'username' => 'admin',
            'email' => $adminUser['email'],
            'role' => 'admin'
        ];
        
        if ($userModel->update($updateData, $adminUser['id'])) {
            echo "<p style='color:green'>✓ Admin role updated successfully</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to update admin role</p>";
        }
    } else {
        echo "<p style='color:green'>✓ Admin role is already correct</p>";
    }
} else {
    echo "<p>Admin user does not exist. Creating new admin user...</p>";
    
    $userId = $userModel->create($adminCredentials);
    if ($userId) {
        echo "<p style='color:green'>✓ Admin user created successfully with ID: " . $userId . "</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to create admin user</p>";
        
        // Check if table exists
        $checkTable = "SHOW TABLES LIKE 'users'";
        $stmt = $db->prepare($checkTable);
        $db->execute($stmt);
        $tableExists = $db->rowCount($stmt) > 0;
        
        if (!$tableExists) {
            echo "<p style='color:red'>✗ The 'users' table does not exist.</p>";
            echo "<p>Creating users table...</p>";
            
            // Create users table
            $createTable = "CREATE TABLE users (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'formateur', 'stagiaire') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            $stmt = $db->prepare($createTable);
            if ($db->execute($stmt)) {
                echo "<p style='color:green'>✓ Users table created successfully</p>";
                
                // Try creating admin user again
                $userId = $userModel->create($adminCredentials);
                if ($userId) {
                    echo "<p style='color:green'>✓ Admin user created successfully with ID: " . $userId . "</p>";
                } else {
                    echo "<p style='color:red'>✗ Still failed to create admin user</p>";
                }
            } else {
                echo "<p style='color:red'>✗ Failed to create users table</p>";
            }
        }
    }
}

// 3. Check session variables
echo "<h2>3. Session Configuration</h2>";
echo "<p>Session name: " . SESSION_NAME . "</p>";
echo "<p>Base URL: " . BASE_URL . "</p>";

// 4. Test admin login
echo "<h2>4. Test Admin Login</h2>";
if ($adminUser || $userId) {
    $user = $userModel->authenticate('admin', 'admin123');
    if ($user) {
        echo "<p style='color:green'>✓ Authentication successful for admin user</p>";
        
        // Set session variables
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('role', $user['role']);
        
        // Check session variables
        if (Session::get('role') === 'admin' && Session::isLoggedIn()) {
            echo "<p style='color:green'>✓ Session variables set correctly</p>";
            echo "<p>Session contains:</p>";
            echo "<ul>";
            echo "<li>user_id: " . Session::get('user_id') . "</li>";
            echo "<li>username: " . Session::get('username') . "</li>";
            echo "<li>role: " . Session::get('role') . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color:red'>✗ Session variables NOT set correctly</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Authentication failed for admin user</p>";
    }
} else {
    echo "<p style='color:red'>✗ Cannot test login - admin user not available</p>";
}

// 5. Check file paths
echo "<h2>5. File Path Check</h2>";
$requiredFiles = [
    'Admin index' => '/admin/index.php',
    'Login script' => '/login.php',
    'User model' => '/models/User.php',
    'Session utility' => '/utils/Session.php',
    'Config file' => '/config/config.php'
];

foreach ($requiredFiles as $name => $path) {
    $fullPath = __DIR__ . $path;
    if (file_exists($fullPath)) {
        echo "<p style='color:green'>✓ $name file exists at: $fullPath</p>";
    } else {
        echo "<p style='color:red'>✗ $name file does NOT exist at: $fullPath</p>";
    }
}

// Final instructions
echo "<h2>Next Steps</h2>";
echo "<p>If all checks passed:</p>";
echo "<ol>";
echo "<li>Try logging in with username <strong>admin</strong> and password <strong>admin123</strong></li>";
echo "<li>If you're still having issues, check your server error logs for additional information</li>";
echo "<li>Make sure your database is properly configured in config.php</li>";
echo "</ol>";

echo "<p><a href='" . BASE_URL . "/login.php' class='btn' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
?> 