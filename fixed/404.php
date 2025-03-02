<?php
// Load configuration if available
@include_once __DIR__ . '/config/config.php';
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Exam Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo $siteName; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 50px;
            background-color: #f5f5f5;
        }
        .error-container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #dc3545;
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-message">Page Not Found</div>
            <p>The page you are looking for does not exist or has been moved.</p>
            
            <div class="mt-4">
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/new_exam_php'; ?>" class="btn btn-primary mr-2">Go to Home</a>
                <a href="<?php echo defined('BASE_URL') ? BASE_URL . '/login.php' : '/new_exam_php/login.php'; ?>" class="btn btn-secondary">Go to Login</a>
            </div>
            
            <div class="mt-5 text-muted">
                <p>Try these alternative pages:</p>
                <ul class="list-unstyled">
                    <li><a href="<?php echo defined('BASE_URL') ? BASE_URL . '/admin_direct.php' : '/new_exam_php/admin_direct.php'; ?>">Alternative Admin Dashboard</a></li>
                    <li><a href="<?php echo defined('BASE_URL') ? BASE_URL . '/debug_admin.php' : '/new_exam_php/debug_admin.php'; ?>">Debug Page</a></li>
                    <li><a href="<?php echo defined('BASE_URL') ? BASE_URL . '/admin/basic.php' : '/new_exam_php/admin/basic.php'; ?>">Basic Admin Page</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
