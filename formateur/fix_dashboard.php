<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is formateur
if (Session::get('role') !== 'formateur') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0066cc;
            margin-top: 0;
        }
        .btn {
            display: inline-block;
            background: #0066cc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .btn:hover {
            background: #0052a3;
        }
        .info {
            background: #e6f7ff;
            border-left: 4px solid #0066cc;
            padding: 10px 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard Fix</h1>
        
        <div class="info">
            <p>This page will help you access the dashboard without the loading spinner issue.</p>
        </div>
        
        <p>Click the button below to access the dashboard with the loading animation disabled:</p>
        
        <a href="<?php echo BASE_URL; ?>/formateur/dashboard.php?no_loading=1" class="btn">Go to Dashboard</a>
        
        <script>
            // Automatically redirect after 2 seconds
            setTimeout(function() {
                window.location.href = '<?php echo BASE_URL; ?>/formateur/dashboard.php?no_loading=1';
            }, 2000);
        </script>
    </div>
</body>
</html> 