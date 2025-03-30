<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define paths
$adminDir = __DIR__ . '/admin';
$adminIncludes = $adminDir . '/includes';

// Check if header and footer exist
if (!file_exists($adminIncludes . '/header.php') || !file_exists($adminIncludes . '/footer.php')) {
    die("Error: Admin includes (header.php and footer.php) must exist before running this script.");
}

// Files to process - specify them explicitly to control the order and which ones to modify
$filesToProcess = [
    'users.php',
    'exams.php',
    'results.php',
    'settings.php',
    'dashboard.php'
];

$updatedFiles = [];
$errorFiles = [];

function fixFile($filePath, $filename) {
    // Read file content
    $content = file_get_contents($filePath);
    
    // Remove any existing header/footer includes to avoid duplication
    $content = preg_replace('/\/\/ Include header.*?require_once __DIR__ \. \'\/includes\/header\.php\';/s', '', $content);
    $content = preg_replace('/\<\?php require_once __DIR__ \. \'\/includes\/footer\.php\'; \?\>/', '', $content);
    
    // Check for duplicate PHP blocks
    // This is a specific fix for users.php which has duplicated PHP code
    if (substr_count($content, '<?php') > 1) {
        // Find the main PHP block (the first one)
        $firstPhpEnd = strpos($content, '?>');
        if ($firstPhpEnd !== false) {
            // Look for the next PHP start after the first PHP end
            $nextPhpStart = strpos($content, '<?php', $firstPhpEnd);
            if ($nextPhpStart !== false) {
                // Look for logical inclusion patterns to avoid removing actual inline PHP code
                $initialBlock = substr($content, 0, $firstPhpEnd + 2); // Include the ?>
                $remainingContent = substr($content, $nextPhpStart);
                
                // Find a good cutoff point in the new PHP block (after includes/declaration sections)
                $cutoffPoint = strpos($remainingContent, '// Process form submissions');
                if ($cutoffPoint !== false) {
                    // We keep the first PHP block and everything after the duplicate initialization
                    $content = $initialBlock . substr($remainingContent, $cutoffPoint);
                }
            }
        }
    }
    
    // Add header include after the first PHP closing tag
    $firstPhpEndPos = strpos($content, '?>');
    if ($firstPhpEndPos !== false) {
        // Insert header include right after the first PHP block
        $beforeHeader = substr($content, 0, $firstPhpEndPos + 2);
        $afterHeader = substr($content, $firstPhpEndPos + 2);
        
        // Add a line break before the header include if needed
        if (substr($beforeHeader, -3) !== "?>\n") {
            $beforeHeader .= "\n";
        }
        
        // Add the header include
        $headerInclude = "\n// Include header\nrequire_once __DIR__ . '/includes/header.php';\n?>\n\n";
        
        // For users.php specifically, add the welcome card right after the header
        if ($filename === 'users.php') {
            $headerInclude .= '<div class="welcome-card">
    <h1 class="welcome-title">Manage Users</h1>
    <p class="welcome-date">Organize and manage system users</p>
</div>

';
        } else if ($filename === 'exams.php') {
            $headerInclude .= '<div class="welcome-card">
    <h1 class="welcome-title">Manage Exams</h1>
    <p class="welcome-date">Create and manage examination materials</p>
</div>

';
        } else if ($filename === 'results.php') {
            $headerInclude .= '<div class="welcome-card">
    <h1 class="welcome-title">Exam Results</h1>
    <p class="welcome-date">View and analyze student performance</p>
</div>

';
        } else if ($filename === 'settings.php') {
            $headerInclude .= '<div class="welcome-card">
    <h1 class="welcome-title">System Settings</h1>
    <p class="welcome-date">Configure system preferences</p>
</div>

';
        } else if ($filename === 'dashboard.php') {
            $headerInclude .= '<div class="welcome-card">
    <h1 class="welcome-title">Admin Dashboard</h1>
    <p class="welcome-date">System overview and quick actions</p>
</div>

';
        }
        
        $content = $beforeHeader . $headerInclude . $afterHeader;
    } else {
        // If no PHP closing tag is found, assume the file structure is different
        $content = "<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and models
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';

// Check if user is admin
Session::checkAdmin();

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class=\"welcome-card\">
    <h1 class=\"welcome-title\">Manage " . ucfirst(str_replace('.php', '', $filename)) . "</h1>
    <p class=\"welcome-date\">Admin Control Panel</p>
</div>

" . $content;
    }
    
    // Add footer include at the end of the file
    if (strpos($content, "require_once __DIR__ . '/includes/footer.php';") === false) {
        $content .= "\n<?php require_once __DIR__ . '/includes/footer.php'; ?>\n";
    }
    
    // Write updated content back to the file
    return file_put_contents($filePath, $content);
}

// Process each file
foreach ($filesToProcess as $filename) {
    $filePath = $adminDir . '/' . $filename;
    
    if (!file_exists($filePath)) {
        $errorFiles[] = $filename . ' (file not found)';
        continue;
    }
    
    // Backup the file first
    $backupPath = $filePath . '.bak';
    if (!copy($filePath, $backupPath)) {
        $errorFiles[] = $filename . ' (backup failed)';
        continue;
    }
    
    // Fix the file
    if (fixFile($filePath, $filename)) {
        $updatedFiles[] = $filename;
    } else {
        $errorFiles[] = $filename . ' (update failed)';
    }
}

// Output results as HTML
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Theme Fix</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #1d1d1f;
            background-color: #f5f5f7;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        h1 {
            color: #1d1d1f;
            font-weight: 700;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        h2 {
            color: #1d1d1f;
            font-size: 18px;
            margin-top: 30px;
            font-weight: 600;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin: 8px 0;
        }
        .success {
            color: #34c759;
        }
        .error {
            color: #ff3b30;
        }
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
        .btn {
            display: inline-block;
            background-color: #0071e3;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 10px;
            transition: all 0.2s ease;
        }
        .btn:hover {
            background-color: #0062cc;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background-color: rgba(0, 0, 0, 0.05);
            color: #1d1d1f;
        }
        .btn-secondary:hover {
            background-color: rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Apple Theme Implementation Results</h1>
        
        <p>The script has processed the admin pages and applied the Apple-inspired theme through the common header and footer.</p>
        
        <h2>Updated Files <span class='success'>(" . count($updatedFiles) . ")</span></h2>";

if (count($updatedFiles) > 0) {
    echo "<ul>";
    foreach ($updatedFiles as $file) {
        echo "<li class='success'>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No files were updated.</p>";
}

echo "<h2>Errors <span class='error'>(" . count($errorFiles) . ")</span></h2>";

if (count($errorFiles) > 0) {
    echo "<ul>";
    foreach ($errorFiles as $file) {
        echo "<li class='error'>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No errors occurred.</p>";
}

echo "<div class='actions'>
    <p>All updated files have been backed up with the .bak extension.</p>
    <a href='admin/index.php' class='btn'>Go to Admin Dashboard</a>
    <a href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "' class='btn btn-secondary'>Run Again</a>
</div>

</div>
</body>
</html>"; 