<?php
// Very basic admin page with minimal dependencies
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple message
echo "<h1>Basic Admin Page</h1>";
echo "<p>If you can see this, the basic admin page is working!</p>";

// Try including minimal dependencies
try {
    require_once __DIR__ . '/../config/config.php';
    echo "<p>Successfully included config.php</p>";
    
    require_once __DIR__ . '/../utils/Session.php';
    echo "<p>Successfully included Session.php</p>";
    
    // Initialize session
    Session::init();
    echo "<p>Successfully initialized session</p>";
    
    // Check login status
    if (Session::isLoggedIn()) {
        $username = Session::get('username');
        $userRole = Session::get('user_role');
        echo "<p>You are logged in as: " . htmlspecialchars($username) . " (Role: " . htmlspecialchars($userRole) . ")</p>";
    } else {
        echo "<p>You are not logged in.</p>";
    }
    
    echo "<p><a href='../login.php'>Go to Login Page</a></p>";
    echo "<p><a href='../admin_direct.php'>Go to Alternative Admin Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>

