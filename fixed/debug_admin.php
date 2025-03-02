<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Admin Page Debugging</h1>";

// Check required files
$files = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/config/Database.php',
    __DIR__ . '/utils/Session.php',
    __DIR__ . '/models/Model.php',
    __DIR__ . '/models/User.php',
    __DIR__ . '/models/Exam.php',
    __DIR__ . '/models/Result.php',
    __DIR__ . '/admin/includes/header.php',
    __DIR__ . '/admin/index.php'
];

echo "<h2>Checking Files:</h2>";
echo "<ul>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<li style='color:green;'>✅ " . htmlspecialchars(basename($file)) . " exists</li>";
    } else {
        echo "<li style='color:red;'>❌ " . htmlspecialchars(basename($file)) . " does not exist</li>";
    }
}
echo "</ul>";

// Include required files
try {
    require_once __DIR__ . '/config/config.php';
    echo "<p style='color:green;'>✅ config.php included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error including config.php: " . $e->getMessage() . "</p>";
}

// Test database connection
echo "<h2>Testing Database Connection:</h2>";
try {
    require_once __DIR__ . '/config/Database.php';
    $db = new Database();
    echo "<p style='color:green;'>✅ Database connection successful</p>";
    
    // Test a simple query
    $stmt = $db->prepare("SHOW TABLES");
    $db->execute($stmt);
    $tables = $db->resultSet($stmt);
    
    echo "<p>Database tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars(reset($table)) . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test User model
echo "<h2>Testing User Model:</h2>";
try {
    require_once __DIR__ . '/models/Model.php';
    require_once __DIR__ . '/models/User.php';
    
    $userModel = new User();
    echo "<p style='color:green;'>✅ User model initialized</p>";
    
    // Get admin user
    $admins = $userModel->getUsersByRole('admin');
    echo "<p>Found " . count($admins) . " admin users</p>";
    
    if (count($admins) > 0) {
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>Username: " . htmlspecialchars($admin['username']) . ", Email: " . htmlspecialchars($admin['email']) . "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ User model error: " . $e->getMessage() . "</p>";
}

// Test Exam model
echo "<h2>Testing Exam Model:</h2>";
try {
    require_once __DIR__ . '/models/Exam.php';
    
    $examModel = new Exam();
    echo "<p style='color:green;'>✅ Exam model initialized</p>";
    
    // Get all exams
    $exams = $examModel->getAll();
    echo "<p>Found " . count($exams) . " exams</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Exam model error: " . $e->getMessage() . "</p>";
}

// Test Result model
echo "<h2>Testing Result Model:</h2>";
try {
    require_once __DIR__ . '/models/Result.php';
    
    $resultModel = new Result();
    echo "<p style='color:green;'>✅ Result model initialized</p>";
    
    // Get all results
    $results = $resultModel->getAll();
    echo "<p>Found " . count($results) . " results</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Result model error: " . $e->getMessage() . "</p>";
}

// Test the exact query from admin/index.php
echo "<h2>Testing Admin Dashboard Query:</h2>";
try {
    $query = "SELECT r.*, e.name as exam_name, u.username as stagiaire_name 
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              JOIN users u ON r.stagiaire_id = u.id
              ORDER BY r.created_at DESC LIMIT 5";
    
    $stmt = $db->prepare($query);
    $db->execute($stmt);
    $recentResults = $db->resultSet($stmt);
    
    echo "<p style='color:green;'>✅ Dashboard query executed successfully</p>";
    echo "<p>Found " . count($recentResults) . " recent results</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Dashboard query error: " . $e->getMessage() . "</p>";
    
    // Try alternative query if the table structure might be different
    try {
        echo "<p>Trying alternative query without joins...</p>";
        $query = "SELECT * FROM results ORDER BY created_at DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $db->execute($stmt);
        $basicResults = $db->resultSet($stmt);
        echo "<p style='color:green;'>✅ Basic results query executed successfully</p>";
        echo "<p>Found " . count($basicResults) . " basic results</p>";
    } catch (Exception $ex) {
        echo "<p style='color:red;'>❌ Basic results query error: " . $ex->getMessage() . "</p>";
    }
}

echo "<h2>Debugging Information:</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Script Path:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Error Reporting Level:</strong> " . error_reporting() . "</p>";

echo "<h2>Next Steps:</h2>";
echo "<p>Based on this debug information, we can determine which part of the admin page is failing.</p>";
echo "<p>Try accessing the login page: <a href='login.php'>login.php</a></p>";
echo "<p>Or try to access the admin page directly: <a href='admin/index.php'>admin/index.php</a></p>";
?>
