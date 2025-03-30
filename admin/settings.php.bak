<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is admin
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Process form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle site settings
    if (isset($_POST['update_site_settings'])) {
        $siteName = trim($_POST['site_name']);
        $siteDescription = trim($_POST['site_description']);
        $adminEmail = trim($_POST['admin_email']);
        
        // Validate inputs
        if (empty($siteName)) {
            $error = "Site name cannot be empty.";
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Update the config.php file with new settings
            // Note: This is a simplified example. In a real application, you might use
            // a database table to store these settings rather than modifying a PHP file.
            
            try {
                $configFile = __DIR__ . '/../config/config.php';
                $configContent = file_get_contents($configFile);
                
                // Replace site name
                $configContent = preg_replace(
                    "/define\('SITE_NAME', '.*?'\);/",
                    "define('SITE_NAME', '" . addslashes($siteName) . "');",
                    $configContent
                );
                
                // Replace admin email if it exists in config
                if (strpos($configContent, "ADMIN_EMAIL") !== false) {
                    $configContent = preg_replace(
                        "/define\('ADMIN_EMAIL', '.*?'\);/",
                        "define('ADMIN_EMAIL', '" . addslashes($adminEmail) . "');",
                        $configContent
                    );
                } else {
                    // Add admin email if it doesn't exist
                    $configContent = str_replace(
                        "define('SITE_NAME',",
                        "define('ADMIN_EMAIL', '" . addslashes($adminEmail) . "');\ndefine('SITE_NAME',",
                        $configContent
                    );
                }
                
                // Similarly for site description, add it if it doesn't exist
                if (strpos($configContent, "SITE_DESCRIPTION") !== false) {
                    $configContent = preg_replace(
                        "/define\('SITE_DESCRIPTION', '.*?'\);/",
                        "define('SITE_DESCRIPTION', '" . addslashes($siteDescription) . "');",
                        $configContent
                    );
                } else {
                    $configContent = str_replace(
                        "define('SITE_NAME',",
                        "define('SITE_DESCRIPTION', '" . addslashes($siteDescription) . "');\ndefine('SITE_NAME',",
                        $configContent
                    );
                }
                
                // Write updated content back to file
                if (file_put_contents($configFile, $configContent)) {
                    $message = "Site settings updated successfully!";
                } else {
                    $error = "Failed to write to config file. Check file permissions.";
                }
            } catch (Exception $e) {
                $error = "Error updating settings: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['clear_logs'])) {
        // Logic to clear logs
        try {
            $logFile = __DIR__ . '/../logs/error.log';
            if (file_exists($logFile)) {
                if (file_put_contents($logFile, '') !== false) {
                    $message = "Error logs cleared successfully!";
                } else {
                    $error = "Failed to clear error logs. Check file permissions.";
                }
            } else {
                $error = "Log file does not exist.";
            }
        } catch (Exception $e) {
            $error = "Error clearing logs: " . $e->getMessage();
        }
    }
}

// Get current settings
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Exam System';
$siteDescription = defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : 'Online examination system';
$adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@example.com';

// Check PHP version and extensions
$phpVersion = phpversion();
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
$extensionStatus = [];

foreach ($requiredExtensions as $ext) {
    $extensionStatus[$ext] = extension_loaded($ext);
}

// Check if logs directory is writable
$logsDir = __DIR__ . '/../logs';
$isLogsDirWritable = is_dir($logsDir) && is_writable($logsDir);

// Check if config file is writable
$configFile = __DIR__ . '/../config/config.php';
$isConfigFileWritable = file_exists($configFile) && is_writable($configFile);

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href='../assets/css/dark-mode.css' rel='stylesheet'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-size: .875rem;
            padding-top: 4.5rem;
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
            background-color:rgb(189, 188, 188);
            border-radius: 0.5rem;
            
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="admin-page bg-gray-100">
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3">
            
        </ul>
    </nav>    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/admin/index.php"><?php echo SITE_NAME; ?></a>
        <ul class="navbar-nav px-3 ml-auto">
            <li class="nav-item text-nowrap mr-3">
                <button id="dark-mode-toggle" class="btn btn-outline-light">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
            </li>
            
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/exams.php">
                                <i class="fas fa-clipboard-list"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin/settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="sidebar-footer mt-auto position-absolute" style="bottom: 20px; width: 100%;">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php" style="padding: 0.75rem 1rem;">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Settings</h1>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Site Settings Card -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-globe"></i> Site Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo BASE_URL; ?>/admin/settings.php">
                            <div class="form-group">
                                <label for="site_name">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($siteName); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="site_description">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="2"><?php echo htmlspecialchars($siteDescription); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="admin_email">Admin Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($adminEmail); ?>" required>
                            </div>
                            <button type="submit" name="update_site_settings" class="btn btn-primary" <?php echo !$isConfigFileWritable ? 'disabled' : ''; ?>>
                                Save Settings
                            </button>
                            <?php if (!$isConfigFileWritable): ?>
                                <div class="text-danger mt-2">
                                    <i class="fas fa-exclamation-triangle"></i> Config file is not writable. Check file permissions.
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- System Information Card -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> System Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th>PHP Version</th>
                                    <td><?php echo $phpVersion; ?></td>
                                </tr>
                                <tr>
                                    <th>Server Software</th>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                </tr>
                                <tr>
                                    <th>Database</th>
                                    <td>MySQL</td>
                                </tr>
                                <tr>
                                    <th>Required Extensions</th>
                                    <td>
                                        <?php foreach ($extensionStatus as $ext => $loaded): ?>
                                            <span class="badge badge-<?php echo $loaded ? 'success' : 'danger'; ?>">
                                                <?php echo $ext; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Logs Directory</th>
                                    <td>
                                        <?php if ($isLogsDirWritable): ?>
                                            <span class="text-success"><i class="fas fa-check"></i> Writable</span>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="fas fa-times"></i> Not writable</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Maintenance Card -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> Maintenance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Error Logs</h5>
                                        <p class="card-text">Clear system error logs. This is useful for troubleshooting.</p>
                                        <form method="post" action="<?php echo BASE_URL; ?>/admin/settings.php">
                                            <button type="submit" name="clear_logs" class="btn btn-warning" <?php echo !$isLogsDirWritable ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash-alt"></i> Clear Logs
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Database Backup</h5>
                                        <p class="card-text">Create a backup of your database. Recommended before major changes.</p>
                                        <a href="#" class="btn btn-info disabled">
                                            <i class="fas fa-database"></i> Backup Database
                                        </a>
                                        <div class="text-muted mt-2">
                                            <small>This feature is not yet implemented.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Settings Card -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-wrench"></i> Advanced Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Advanced settings will be implemented in a future update.
                        </div>
                        
                        <p>Possible future settings include:</p>
                        <ul>
                            <li>Email notification settings</li>
                            <li>File upload configurations</li>
                            <li>Security settings (session timeout, password policies)</li>
                            <li>Theme customization</li>
                            <li>System-wide announcements</li>
                        </ul>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
<script src="../assets/js/dark-mode.js"></script>
