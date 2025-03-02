<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';

// Start the session
Session::init();

// Check login status without redirecting
$isLoggedIn = Session::isLoggedIn();
$username = Session::get('username');
$userRole = Session::get('user_role');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Admin Dashboard Access</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($isLoggedIn): ?>
                            <div class="alert alert-success">
                                <h5>Login Status: <span class="text-success">Logged In</span></h5>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                                <p><strong>Role:</strong> <?php echo htmlspecialchars($userRole); ?></p>
                                
                                <?php if ($userRole === 'admin'): ?>
                                    <p class="text-success">✅ You have admin privileges</p>
                                    
                                    <div class="mt-4">
                                        <h5>Admin Dashboard Links:</h5>
                                        <ul>
                                            <li><a href="admin/index.php">Regular Admin Dashboard</a> (May not work)</li>
                                            <li><a href="admin/users.php">Manage Users</a></li>
                                            <li><a href="admin/exams.php">Manage Exams</a></li>
                                        </ul>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h5>Admin Actions:</h5>
                                        <form method="post" action="auto_fix_admin.php" class="d-inline">
                                            <button type="submit" class="btn btn-warning">Re-Fix Admin User</button>
                                        </form>
                                        <a href="logout.php" class="btn btn-danger">Logout</a>
                                        
                                        <hr>
                                        
                                        <h6>Stats:</h6>
                                        <?php
                                        try {
                                            require_once __DIR__ . '/models/User.php';
                                            $userModel = new User();
                                            $formateurCount = count($userModel->getUsersByRole('formateur'));
                                            $stagiaireCount = count($userModel->getUsersByRole('stagiaire'));
                                            
                                            echo "<p>Formateurs: $formateurCount</p>";
                                            echo "<p>Stagiaires: $stagiaireCount</p>";
                                        } catch (Exception $e) {
                                            echo "<p class='text-danger'>Error loading stats: " . $e->getMessage() . "</p>";
                                        }
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-warning">⚠️ You are logged in but don't have admin privileges</p>
                                    <p>Your current role is: <?php echo htmlspecialchars($userRole); ?></p>
                                    <a href="logout.php" class="btn btn-primary">Logout</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5>Login Status: <span class="text-danger">Not Logged In</span></h5>
                                <p>You need to login with admin credentials to access the dashboard.</p>
                                <a href="login.php" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 p-3 bg-light">
                            <h5>Troubleshooting Help:</h5>
                            <p>If you're having trouble accessing the admin dashboard:</p>
                            <ol>
                                <li>Make sure you're logged in with admin credentials</li>
                                <li>Check that your session is working correctly</li>
                                <li>Verify that the database connection is working</li>
                                <li>Look for any PHP errors in your logs</li>
                            </ol>
                            <a href="debug_admin.php" class="btn btn-info">Run Diagnostic Tool</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
