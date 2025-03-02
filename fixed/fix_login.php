<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include necessary files
require_once __DIR__ . '/config/config.php';

echo "<h2>Login System Diagnostic</h2>";

// Check if config constants are set
echo "<h3>Configuration Check:</h3>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'Not defined') . "<br>";
echo "SITE_NAME: " . (defined('SITE_NAME') ? SITE_NAME : 'Not defined') . "<br>";
echo "SESSION_NAME: " . (defined('SESSION_NAME') ? SESSION_NAME : 'Not defined') . "<br>";

// Check database connection
echo "<h3>Database Connection Check:</h3>";
try {
    require_once __DIR__ . '/config/Database.php';
    $db = new Database();
    $connection = $db->getConnection();
    if ($connection) {
        echo "Database connection successful!<br>";
        
        // Check if required tables exist
        $tables = ['users', 'exams', 'questions', 'results'];
        echo "<h4>Checking tables:</h4>";
        foreach ($tables as $table) {
            $query = "SHOW TABLES LIKE '$table'";
            $stmt = $db->prepare($query);
            $db->execute($stmt);
            $result = $db->single($stmt);
            
            if ($result) {
                echo "Table '$table': <span style='color:green'>Found</span><br>";
                
                // If it's the users table, check if there are any users
                if ($table === 'users') {
                    $query = "SELECT COUNT(*) as count FROM users";
                    $stmt = $db->prepare($query);
                    $db->execute($stmt);
                    $result = $db->single($stmt);
                    
                    if ($result && $result['count'] > 0) {
                        echo "Users in database: " . $result['count'] . "<br>";
                    } else {
                        echo "No users found in database.<br>";
                    }
                }
            } else {
                echo "Table '$table': <span style='color:red'>Not found</span><br>";
            }
        }
    } else {
        echo "Database connection failed!<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Check if important files exist
echo "<h3>File Structure Check:</h3>";
$importantFiles = [
    '/login.php',
    '/config/config.php',
    '/config/Database.php',
    '/utils/Session.php',
    '/models/User.php',
    '/models/Exam.php',
    '/models/Result.php',
    '/models/Question.php',
    '/formateur/dashboard.php',
    '/stagiaire/dashboard.php'
];

foreach ($importantFiles as $file) {
    $filePath = __DIR__ . $file;
    echo "File '" . $file . "': " . (file_exists($filePath) ? "<span style='color:green'>Found</span>" : "<span style='color:red'>Not found</span>") . "<br>";
}

// Provide links to key pages
echo "<h3>Quick Links:</h3>";
echo "<a href='" . BASE_URL . "/login.php'>Login Page</a><br>";
echo "<a href='" . BASE_URL . "/create_test_users.php'>Create Test Users</a><br>";
echo "<a href='" . BASE_URL . "/formateur/dashboard.php'>Formateur Dashboard</a><br>";
echo "<a href='" . BASE_URL . "/stagiaire/dashboard.php'>Stagiaire Dashboard</a><br>";

echo "<h3>Recommendations:</h3>";
echo "1. Make sure your database is properly set up. Run setup_database.php if needed.<br>";
echo "2. Create test users with create_test_users.php.<br>";
echo "3. Check your server configuration - ensure PHP is running and the Apache server is configured correctly.<br>";
echo "4. Check that 'new_exam_php' is in the correct location within your XAMPP htdocs directory.<br>";
?>
