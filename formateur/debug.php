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
    <title>Debug Dashboard</title>
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
        .debug-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        button {
            background: #0066cc;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0052a3;
        }
        #result {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Formateur Dashboard Debug</h1>
    
    <div class="debug-info">
        <h2>Session Information</h2>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <h2>Server Information</h2>
        <pre><?php print_r($_SERVER); ?></pre>
    </div>

    <h2>Check Dashboard Loading</h2>
    <button id="testLoading">Test Dashboard Loading</button>
    <div id="result"></div>

    <script>
        // Error handler to catch any JavaScript errors
        window.onerror = function(message, source, lineno, colno, error) {
            document.getElementById('result').innerHTML += 
                '<p class="error">Error: ' + message + '<br>' +
                'Source: ' + source + '<br>' +
                'Line: ' + lineno + '</p>';
            return true;
        };

        document.getElementById('testLoading').addEventListener('click', function() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Testing dashboard loading...</p>';
            
            // Create an invisible iframe to load the dashboard
            const iframe = document.createElement('iframe');
            iframe.style.width = '1px';
            iframe.style.height = '1px';
            iframe.style.position = 'absolute';
            iframe.style.top = '-100px';
            iframe.style.left = '-100px';
            
            // Handle load event
            iframe.onload = function() {
                resultDiv.innerHTML += '<p class="success">Dashboard page loaded in iframe!</p>';
                try {
                    // Try to access the iframe's content
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    resultDiv.innerHTML += '<p>Dashboard loaded with title: ' + iframeDoc.title + '</p>';
                    
                    // Check for loading spinner
                    const loadingElement = iframeDoc.querySelector('.loading');
                    if (loadingElement) {
                        resultDiv.innerHTML += '<p>Loading animation found. Style: ' + 
                            (loadingElement.style.display || 'not set') + '</p>';
                    } else {
                        resultDiv.innerHTML += '<p class="error">Loading animation not found!</p>';
                    }
                } catch (e) {
                    resultDiv.innerHTML += '<p class="error">Error accessing iframe content: ' + e.message + '</p>';
                }
            };
            
            // Handle error event
            iframe.onerror = function() {
                resultDiv.innerHTML += '<p class="error">Failed to load dashboard in iframe!</p>';
            };
            
            // Set the source and append to document
            iframe.src = '<?php echo BASE_URL; ?>/formateur/dashboard.php';
            document.body.appendChild(iframe);
        });
    </script>
</body>
</html> 