<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define paths
$file = __DIR__ . '/admin/users.php';
$backupFile = $file . '.bak';

// Check if file exists
if (!file_exists($file)) {
    die("Error: admin/users.php file does not exist.");
}

// Backup original file
if (!copy($file, $backupFile)) {
    die("Error: Failed to create backup of users.php");
}

// Read file content
$content = file_get_contents($file);

// Extract PHP code before HTML
$phpCodePattern = '/^(.*?)<!DOCTYPE html>/s';
preg_match($phpCodePattern, $content, $phpMatches);

if (empty($phpMatches)) {
    die("Error: Could not find DOCTYPE in users.php");
}

$phpCode = $phpMatches[1];

// Create new content
$newContent = $phpCode;

// Add the include header line
$newContent .= "\n// Include header\nrequire_once __DIR__ . '/includes/header.php';\n?>\n\n";

// Add welcome card
$newContent .= '<div class="welcome-card">
    <h1 class="welcome-title">Manage Users</h1>
    <p class="welcome-date">Organize and manage system users</p>
</div>

';

// Find the start of the content after the body tag
$bodyStartPos = strpos($content, '<body');
if ($bodyStartPos === false) {
    die("Error: Could not find body tag in users.php");
}

$bodyContentStartPos = strpos($content, '>', $bodyStartPos) + 1;

// Find the alerts section
$alertsStartPos = strpos($content, '<?php if (!empty($message))', $bodyContentStartPos);
if ($alertsStartPos === false) {
    die("Error: Could not find alerts section in users.php");
}

// Extract the content between alerts and end of body
$bodyEndPos = strrpos($content, '</body>');
if ($bodyEndPos === false) {
    die("Error: Could not find closing body tag in users.php");
}

$mainContent = substr($content, $alertsStartPos, $bodyEndPos - $alertsStartPos);

// Add the main content
$newContent .= $mainContent;

// Add the footer include
$newContent .= "\n<?php require_once __DIR__ . '/includes/footer.php'; ?>";

// Write new content to file
if (file_put_contents($file, $newContent)) {
    echo "Success: users.php has been updated to use the header and footer includes.<br>";
    echo "A backup of the original file has been saved as users.php.bak";
} else {
    echo "Error: Failed to update users.php";
}
?> 