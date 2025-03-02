<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>XAMPP Environment Check</h1>";

// PHP Version
echo "<h3>PHP Version:</h3>";
echo "Current PHP version: " . phpversion() . "<br>";

// Check if we're running under Apache
echo "<h3>Web Server:</h3>";
if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
    echo "Running on Apache: <span style='color:green'>Yes</span><br>";
    echo "Server software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
} else {
    echo "Running on Apache: <span style='color:red'>No</span> - Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
}

// Document Root
echo "<h3>Document Root:</h3>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current script path: " . __FILE__ . "<br>";

// Check MySQL connection
echo "<h3>MySQL Connection:</h3>";
if (extension_loaded('mysqli')) {
    echo "MySQLi extension: <span style='color:green'>Loaded</span><br>";
    
    $mysqli = @new mysqli('localhost', 'root', '');
    if (!$mysqli->connect_error) {
        echo "MySQL connection: <span style='color:green'>Successful</span><br>";
        echo "MySQL server info: " . $mysqli->server_info . "<br>";
        
        // Check if our database exists
        $result = $mysqli->query("SHOW DATABASES LIKE 'exam_management'");
        if ($result->num_rows > 0) {
            echo "Database 'exam_management': <span style='color:green'>Found</span><br>";
        } else {
            echo "Database 'exam_management': <span style='color:red'>Not found</span><br>";
        }
        
        $mysqli->close();
    } else {
        echo "MySQL connection: <span style='color:red'>Failed</span> - " . $mysqli->connect_error . "<br>";
    }
} else {
    echo "MySQLi extension: <span style='color:red'>Not loaded</span><br>";
}

// PDO availability 
echo "<h3>PDO Support:</h3>";
if (extension_loaded('pdo')) {
    echo "PDO extension: <span style='color:green'>Loaded</span><br>";
    
    if (extension_loaded('pdo_mysql')) {
        echo "PDO MySQL driver: <span style='color:green'>Loaded</span><br>";
        
        try {
            $pdo = new PDO('mysql:host=localhost;', 'root', '');
            echo "PDO MySQL connection: <span style='color:green'>Successful</span><br>";
            
            $pdoDrivers = PDO::getAvailableDrivers();
            echo "Available PDO drivers: " . implode(', ', $pdoDrivers) . "<br>";
        } catch (PDOException $e) {
            echo "PDO MySQL connection: <span style='color:red'>Failed</span> - " . $e->getMessage() . "<br>";
        }
    } else {
        echo "PDO MySQL driver: <span style='color:red'>Not loaded</span><br>";
    }
} else {
    echo "PDO extension: <span style='color:red'>Not loaded</span><br>";
}

// PHP Info for directory and permissions
echo "<h3>Important Settings:</h3>";
echo "include_path: " . ini_get('include_path') . "<br>";
echo "extension_dir: " . ini_get('extension_dir') . "<br>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";

// Check session settings
echo "<h3>Session Configuration:</h3>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "<br>";

// Check for important project files
echo "<h3>Project Files:</h3>";
$importantFiles = [
    '/config/config.php',
    '/config/Database.php',
    '/models/Model.php',
    '/models/User.php',
    '/models/Exam.php',
    '/models/Result.php',
    '/models/Question.php',
    '/utils/Session.php',
    '/utils/Validator.php',
    '/login.php',
    '/formateur/dashboard.php',
    '/stagiaire/dashboard.php'
];

foreach ($importantFiles as $file) {
    $filePath = __DIR__ . $file;
    echo "File '" . $file . "': " . (file_exists($filePath) ? "<span style='color:green'>Found</span>" : "<span style='color:red'>Not found</span>") . "<br>";
}

// URL access check
echo "<h3>URL Access Check:</h3>";
$base_url = 'http://localhost/new_exam_php';
$test_urls = [
    '/login.php',
    '/index.php',
    '/formateur/dashboard.php',
    '/stagiaire/dashboard.php'
];

echo "Checking URLs (this only tests if the file exists, not if it works properly):<br>";
foreach ($test_urls as $url) {
    $full_url = $base_url . $url;
    $file_headers = @get_headers($full_url);
    
    if($file_headers && strpos($file_headers[0], '404') === false) {
        echo "$full_url: <span style='color:green'>Accessible</span><br>";
    } else {
        echo "$full_url: <span style='color:red'>Not accessible</span><br>";
    }
}

echo "<h3>Next Steps:</h3>";
echo "1. Make sure Apache and MySQL are running in XAMPP control panel<br>";
echo "2. Ensure the project is in the correct location (should be in xampp/htdocs/new_exam_php)<br>";
echo "3. Check that the database is created and contains all required tables<br>";
echo "4. Try accessing <a href='$base_url/fix_login.php'>fix_login.php</a> to diagnose specific issues<br>";
echo "5. Create test users with <a href='$base_url/create_test_users.php'>create_test_users.php</a><br>";
?>
