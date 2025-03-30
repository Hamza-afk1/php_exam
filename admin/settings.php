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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin-theme.css" rel="stylesheet">
    <link href="../assets/css/dark-mode.css" rel="stylesheet">
    <style>
        .system-info-card {
            transition: all 0.3s ease;
        }
        
        .system-info-card:hover {
            transform: translateY(-4px);
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-good {
            background-color: var(--apple-green);
        }
        
        .status-bad {
            background-color: #ff3b30;
        }
        
        .settings-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-right: 15px;
            background: linear-gradient(135deg, rgba(0,113,227,0.1) 0%, rgba(0,113,227,0.2) 100%);
            color: var(--apple-blue);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/admin/index.php">
            <i class="fas fa-graduation-cap mr-2"></i><?php echo SITE_NAME; ?>
        </a>
        <div class="ml-auto">
            <button id="dark-mode-toggle" class="btn btn-outline-secondary">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/index.php">
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
                <div class="sidebar-footer">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="page-header d-flex justify-content-between align-items-center pt-3">
                    <h1>System Settings</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <!-- Site Settings Card -->
                        <div class="card mb-4">
                            <div class="card-header d-flex align-items-center">
                                <div class="settings-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <h5 class="mb-0">Site Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="<?php echo BASE_URL; ?>/admin/settings.php">
                                    <div class="form-group">
                                        <label for="site_name">Site Name</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" 
                                               value="<?php echo htmlspecialchars($siteName); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="site_description">Site Description</label>
                                        <textarea class="form-control" id="site_description" name="site_description" 
                                                  rows="3"><?php echo htmlspecialchars($siteDescription); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="admin_email">Admin Email</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                               value="<?php echo htmlspecialchars($adminEmail); ?>" required>
                                    </div>
                                    <button type="submit" name="update_site_settings" class="btn btn-primary" 
                                            <?php echo !$isConfigFileWritable ? 'disabled' : ''; ?>>
                                        <i class="fas fa-save mr-2"></i>Save Settings
                                    </button>
                                    <?php if (!$isConfigFileWritable): ?>
                                        <div class="alert alert-danger mt-3">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            Config file is not writable. Check file permissions.
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <!-- System Maintenance Card -->
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="settings-icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <h5 class="mb-0">System Maintenance</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="<?php echo BASE_URL; ?>/admin/settings.php" class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Clear Error Logs</h6>
                                            <p class="text-muted mb-0">Remove all entries from the error log file</p>
                                        </div>
                                        <button type="submit" name="clear_logs" class="btn btn-outline-danger" 
                                                <?php echo !$isLogsDirWritable ? 'disabled' : ''; ?>>
                                            <i class="fas fa-trash-alt mr-2"></i>Clear Logs
                                        </button>
                                    </div>
                                    <?php if (!$isLogsDirWritable): ?>
                                        <div class="alert alert-danger mt-3">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            Logs directory is not writable. Check directory permissions.
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- System Information Card -->
                        <div class="card system-info-card">
                            <div class="card-header d-flex align-items-center">
                                <div class="settings-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <h5 class="mb-0">System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h6 class="mb-3">PHP Version</h6>
                                    <p class="mb-0">
                                        <span class="status-indicator <?php echo version_compare($phpVersion, '7.0.0', '>=') ? 'status-good' : 'status-bad'; ?>"></span>
                                        <?php echo $phpVersion; ?>
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <h6 class="mb-3">Required Extensions</h6>
                                    <?php foreach ($extensionStatus as $ext => $loaded): ?>
                                        <p class="mb-2">
                                            <span class="status-indicator <?php echo $loaded ? 'status-good' : 'status-bad'; ?>"></span>
                                            <?php echo $ext; ?>
                                        </p>
                                    <?php endforeach; ?>
                                </div>

                                <div>
                                    <h6 class="mb-3">File Permissions</h6>
                                    <p class="mb-2">
                                        <span class="status-indicator <?php echo $isConfigFileWritable ? 'status-good' : 'status-bad'; ?>"></span>
                                        Config File
                                    </p>
                                    <p class="mb-0">
                                        <span class="status-indicator <?php echo $isLogsDirWritable ? 'status-good' : 'status-bad'; ?>"></span>
                                        Logs Directory
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
</body>
</html>