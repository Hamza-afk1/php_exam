<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Loading Animation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #0066cc;
        }
        .card {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            background: #0066cc;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 10px;
        }
        .button:hover {
            background: #0052a3;
        }
        pre {
            background: #eee;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Fix Loading Animation</h1>
    
    <div class="card">
        <h2>Loading Animation Fix</h2>
        <p>This page will add a CSS override to fix the loading animation issue on the formateur dashboard.</p>
        
        <?php
        // Create the CSS override file
        $cssContent = <<<CSS
/* Loading Animation Override */
.loading {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}
body.loaded .loading {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}
CSS;

        $cssPath = __DIR__ . '/includes/loading-override.css';
        $success = file_put_contents($cssPath, $cssContent);
        
        if ($success !== false) {
            echo '<p class="success">✅ Successfully created CSS override file!</p>';
            
            // Now add the CSS link to the header file
            $headerPath = __DIR__ . '/includes/header.php';
            $headerContent = file_get_contents($headerPath);
            
            // Check if the override is already included
            if (strpos($headerContent, 'loading-override.css') === false) {
                // Find the position to insert the new CSS link
                $insertPos = strpos($headerContent, '</head>');
                if ($insertPos !== false) {
                    $newLink = '<link href="<?php echo BASE_URL; ?>/formateur/includes/loading-override.css" rel="stylesheet">' . "\n    ";
                    $newHeaderContent = substr_replace($headerContent, $newLink, $insertPos, 0);
                    $headerSuccess = file_put_contents($headerPath, $newHeaderContent);
                    
                    if ($headerSuccess !== false) {
                        echo '<p class="success">✅ Successfully added CSS override to header file!</p>';
                    } else {
                        echo '<p>Failed to update header file. Please add the following line manually before the &lt;/head&gt; tag:</p>';
                        echo '<pre>&lt;link href="<?php echo BASE_URL; ?>/formateur/includes/loading-override.css" rel="stylesheet"&gt;</pre>';
                    }
                } else {
                    echo '<p>Could not find &lt;/head&gt; tag in header file. Please add the following line manually before the &lt;/head&gt; tag:</p>';
                    echo '<pre>&lt;link href="<?php echo BASE_URL; ?>/formateur/includes/loading-override.css" rel="stylesheet"&gt;</pre>';
                }
            } else {
                echo '<p class="success">✅ CSS override is already included in the header file!</p>';
            }
        } else {
            echo '<p>Failed to create CSS override file. Please create it manually at: ' . htmlspecialchars($cssPath) . '</p>';
            echo '<pre>' . htmlspecialchars($cssContent) . '</pre>';
        }
        ?>
        
        <a href="<?php echo BASE_URL; ?>/formateur/dashboard.php" class="button">Go to Dashboard</a>
    </div>
</body>
</html> 