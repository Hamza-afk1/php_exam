<?php
// Simple diagnostic page to help with navigation and server issues

// Basic PHP server check
$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? 'Unknown';
$requestUri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
$currentScript = __FILE__;
$currentDir = __DIR__;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management - Start</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/dark-mode.css" rel="stylesheet">
    <style>
        body { 
            padding: 20px; 
            background-color: #f5f5f5;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            font-weight: bold;
        }
        .btn-lg {
            margin-top: 10px;
            margin-right: 10px;
        }
        .system-info {
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="text-center mb-4">Exam Management System - Start Page</h1>
                
                <div class="text-right p-4">
                    <button id="dark-mode-toggle" class="btn btn-outline-secondary">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Navigation Options
                    </div>
                    <div class="card-body">
                        <p>Please choose one of the following options:</p>
                        
                        <a href="login.php" class="btn btn-primary btn-lg">Go to Login Page</a>
                        <a href="auto_fix_admin.php" class="btn btn-success btn-lg">Fix Admin User</a>
                        <a href="fix_redirects.php" class="btn btn-warning btn-lg">Fix Redirects</a>
                        <a href="setup_database.php" class="btn btn-info btn-lg">Setup Database</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        Direct Access URLs (Try these if links above don't work)
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="http://localhost/new_exam_php/login.php" class="list-group-item list-group-item-action">
                                http://localhost/new_exam_php/login.php
                            </a>
                            <a href="http://localhost/new_exam_php/admin/index.php" class="list-group-item list-group-item-action">
                                http://localhost/new_exam_php/admin/index.php (Admin Dashboard)
                            </a>
                            <a href="http://127.0.0.1/new_exam_php/login.php" class="list-group-item list-group-item-action">
                                http://127.0.0.1/new_exam_php/login.php (Try with IP instead of localhost)
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        Server Information (For Troubleshooting)
                    </div>
                    <div class="card-body system-info">
                        <p><strong>PHP Version:</strong> <?php echo htmlspecialchars($phpVersion); ?></p>
                        <p><strong>Server Software:</strong> <?php echo htmlspecialchars($serverSoftware); ?></p>
                        <p><strong>Document Root:</strong> <?php echo htmlspecialchars($documentRoot); ?></p>
                        <p><strong>Script Path:</strong> <?php echo htmlspecialchars($scriptPath); ?></p>
                        <p><strong>Request URI:</strong> <?php echo htmlspecialchars($requestUri); ?></p>
                        <p><strong>Current Script:</strong> <?php echo htmlspecialchars($currentScript); ?></p>
                        <p><strong>Current Directory:</strong> <?php echo htmlspecialchars($currentDir); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-success text-white">
                        File Check
                    </div>
                    <div class="card-body">
                        <h5>Important Files:</h5>
                        <ul>
                            <?php
                            $files = [
                                'login.php', 
                                'admin/index.php',
                                'admin/includes/header.php',
                                'formateur/index.php',
                                'stagiaire/index.php',
                                'config/config.php',
                                'utils/Session.php'
                            ];
                            
                            foreach ($files as $file) {
                                $filePath = __DIR__ . '/' . $file;
                                $exists = file_exists($filePath);
                                $icon = $exists ? '✅' : '❌';
                                $textClass = $exists ? 'text-success' : 'text-danger';
                                
                                echo "<li class='$textClass'>$icon $file " . 
                                     ($exists ? "exists" : "is missing") . "</li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>
