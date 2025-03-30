<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define path to admin directory
$adminDir = __DIR__ . '/admin';
$adminIncludes = $adminDir . '/includes';

// Check if header and footer exist
if (!file_exists($adminIncludes . '/header.php') || !file_exists($adminIncludes . '/footer.php')) {
    die("Error: Admin includes (header.php and footer.php) must exist before running this script.");
}

// Get all PHP files in admin directory
$files = glob($adminDir . '/*.php');
$updatedFiles = [];
$skippedFiles = [];

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip files that are already updated or don't need updating
    if (in_array($filename, ['index.php', 'includes/header.php', 'includes/footer.php'])) {
        $skippedFiles[] = $filename . ' (core file)';
        continue;
    }
    
    // Read file content
    $content = file_get_contents($file);
    
    // Check if file already includes header/footer
    if (
        strpos($content, "require_once __DIR__ . '/includes/header.php'") !== false &&
        strpos($content, "require_once __DIR__ . '/includes/footer.php'") !== false
    ) {
        $skippedFiles[] = $filename . ' (already updated)';
        continue;
    }
    
    // Check if file contains <!DOCTYPE html>
    $hasDoctype = (strpos($content, '<!DOCTYPE html>') !== false);
    
    if (!$hasDoctype) {
        $skippedFiles[] = $filename . ' (no DOCTYPE found)';
        continue;
    }
    
    // Backup original file
    file_put_contents($file . '.bak', $content);
    
    // Extract PHP code before HTML
    $phpCodePattern = '/^(.*?)<!DOCTYPE html>/s';
    preg_match($phpCodePattern, $content, $phpMatches);
    $phpCode = isset($phpMatches[1]) ? $phpMatches[1] : '';
    
    // Find the closing body and html tags
    $bodyEndPos = strrpos($content, '</body>');
    $htmlEndPos = strrpos($content, '</html>');
    
    // Extract the content between DOCTYPE and body end
    $htmlBodyContent = '';
    if ($bodyEndPos !== false) {
        $doctypePos = strpos($content, '<!DOCTYPE html>');
        $htmlBodyContent = substr($content, $doctypePos + strlen('<!DOCTYPE html>'), $bodyEndPos - $doctypePos - strlen('<!DOCTYPE html>'));
    }
    
    // Check if we could extract the HTML body content
    if (empty($htmlBodyContent)) {
        $skippedFiles[] = $filename . ' (could not extract HTML body)';
        continue;
    }
    
    // Create new content with header/footer includes
    $newContent = $phpCode;
    
    // Add header include
    $newContent .= "\n// Include header\n";
    $newContent .= "require_once __DIR__ . '/includes/header.php';\n";
    $newContent .= "?>\n\n";
    
    // Clean up HTML body content
    // Find and remove <head> section
    $headStartPos = strpos($htmlBodyContent, '<head>');
    $headEndPos = strpos($htmlBodyContent, '</head>');
    
    if ($headStartPos !== false && $headEndPos !== false) {
        $htmlBodyContent = substr($htmlBodyContent, $headEndPos + 7); // Remove head section
    }
    
    // Find and remove opening body tag
    $bodyStartPos = strpos($htmlBodyContent, '<body');
    $bodyStartEndPos = strpos($htmlBodyContent, '>', $bodyStartPos);
    
    if ($bodyStartPos !== false && $bodyStartEndPos !== false) {
        $htmlBodyContent = substr($htmlBodyContent, $bodyStartEndPos + 1);
    }
    
    // Add the main content
    $newContent .= $htmlBodyContent;
    
    // Add footer include
    $newContent .= "\n<?php require_once __DIR__ . '/includes/footer.php'; ?>";
    
    // Write new content to file
    if (file_put_contents($file, $newContent)) {
        $updatedFiles[] = $filename;
    } else {
        $skippedFiles[] = $filename . ' (write failed)';
    }
}

// Output results
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Pages Fix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #3498db;
            margin-top: 30px;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
        }
        .success {
            color: #27ae60;
        }
        .skipped {
            color: #e67e22;
        }
        .error {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <h1>Admin Pages Update Results</h1>
    
    <p>This script has checked all PHP files in the admin directory and updated them to use the common header and footer includes.</p>
    
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

echo "<h2>Skipped Files <span class='skipped'>(" . count($skippedFiles) . ")</span></h2>";

if (count($skippedFiles) > 0) {
    echo "<ul>";
    foreach ($skippedFiles as $file) {
        echo "<li class='skipped'>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No files were skipped.</p>";
}

echo "<p>The original files have been backed up with the .bak extension.</p>";
echo "<p><a href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>Run Again</a> | <a href='admin/index.php'>Go to Admin Dashboard</a></p>";

echo "</body></html>"; 