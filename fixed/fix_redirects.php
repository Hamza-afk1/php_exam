<?php
// Fix redirect issues script

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';

echo "<h1>Fixing Redirect Issues</h1>";

// Check for proper BASE_URL definition
echo "<h2>1. Checking BASE_URL</h2>";
$serverName = $_SERVER['SERVER_NAME'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$detectedBaseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $serverName . $scriptPath;

echo "<p>Current BASE_URL: " . BASE_URL . "</p>";
echo "<p>Detected BASE_URL: " . $detectedBaseUrl . "</p>";

// Check if header.php files exist
echo "<h2>2. Checking Important Files</h2>";

$criticalFiles = [
    '/admin/index.php',
    '/admin/includes/header.php',
    '/formateur/index.php',
    '/stagiaire/index.php'
];

foreach ($criticalFiles as $file) {
    $fullPath = __DIR__ . $file;
    if (file_exists($fullPath)) {
        echo "<p style='color:green;'>✅ Found: " . htmlspecialchars($file) . "</p>";
    } else {
        echo "<p style='color:red;'>❌ Missing: " . htmlspecialchars($file) . "</p>";
    }
}

// Fix missing directories and files if needed
$missingDirs = [];
if (!file_exists(__DIR__ . '/formateur')) {
    $missingDirs[] = 'formateur';
}

if (!file_exists(__DIR__ . '/stagiaire')) {
    $missingDirs[] = 'stagiaire';
}

if (!empty($missingDirs)) {
    echo "<h2>3. Creating Missing Directories</h2>";
    
    foreach ($missingDirs as $dir) {
        mkdir(__DIR__ . '/' . $dir, 0755, true);
        
        // Create a basic index.php file
        $indexContent = '<?php
// Include header
require_once __DIR__ . \'/includes/header.php\';
?>

<div class="container-fluid">
    <div class="row">
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">' . ucfirst($dir) . ' Dashboard</h1>
            </div>
            
            <div class="alert alert-info">
                <p>Welcome to the ' . ucfirst($dir) . ' Dashboard!</p>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
require_once __DIR__ . \'/includes/footer.php\';
?>';
        
        // Create includes directory
        mkdir(__DIR__ . '/' . $dir . '/includes', 0755, true);
        
        // Create header.php
        $headerContent = '<?php
// Include required files
require_once __DIR__ . \'/../../config/config.php\';
require_once __DIR__ . \'/../../utils/Session.php\';

// Check if user is ' . $dir . '
Session::check' . ucfirst($dir) . '();

// Get user data
$username = Session::get(\'username\');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . ucfirst($dir) . ' Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-size: .875rem;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #007bff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/' . $dir . '/index.php"><?php echo SITE_NAME; ?></a>
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/logout.php">Sign out</a>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/' . $dir . '/index.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <!-- Add more menu items as needed -->
                    </ul>
                </div>
            </nav>';
        
        // Create footer.php
        $footerContent = '            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>';
        
        // Write the files
        file_put_contents(__DIR__ . '/' . $dir . '/index.php', $indexContent);
        file_put_contents(__DIR__ . '/' . $dir . '/includes/header.php', $headerContent);
        file_put_contents(__DIR__ . '/' . $dir . '/includes/footer.php', $footerContent);
        
        echo "<p style='color:green;'>✅ Created directory and basic files for " . htmlspecialchars($dir) . "</p>";
    }
}

// Create a logout.php file if it doesn't exist
if (!file_exists(__DIR__ . '/logout.php')) {
    $logoutContent = '<?php
// Include session management
require_once __DIR__ . \'/utils/Session.php\';

// Initialize the session
Session::init();

// Destroy the session and redirect to login
Session::destroy();
header(\'Location: login.php\');
exit;
?>';
    
    file_put_contents(__DIR__ . '/logout.php', $logoutContent);
    echo "<p style='color:green;'>✅ Created logout.php file</p>";
}

echo "<h2>Summary</h2>";
echo "<p>Fixed potential issues with redirect paths and created missing files/directories.</p>";
echo "<p>You should now be able to login and be properly redirected to the admin dashboard.</p>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='login.php' style='padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Go to Login Page</a>";
echo "<a href='auto_fix_admin.php' style='padding: 10px 20px; background-color: #5cb85c; color: white; text-decoration: none; border-radius: 4px;'>Fix Admin User Again</a>";
echo "</div>";
?>
