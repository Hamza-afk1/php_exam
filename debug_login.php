<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';
require_once __DIR__ . '/models/User.php';

// Initialize session
Session::init();

$debug_info = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $debug_info['username'] = $username;
    $debug_info['password_length'] = strlen($password);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $userModel = new User();
        
        // Debug database connection
        try {
            $db = new Database();
            $debug_info['database_connection'] = 'Success';
        } catch (Exception $e) {
            $debug_info['database_connection'] = 'Failed: ' . $e->getMessage();
        }
        
        // Try to find user
        $user = $userModel->findByUsername($username);
        $debug_info['user_found'] = $user ? 'Yes' : 'No';
        
        if ($user) {
            $debug_info['user_role'] = $user['role'];
            $debug_info['stored_hash'] = $user['password'];
            $debug_info['hash_info'] = password_get_info($user['password']);
            
            // Try password verification
            $verify_result = password_verify($password, $user['password']);
            $debug_info['password_verify'] = $verify_result ? 'Success' : 'Failed';
            
            if ($verify_result) {
                $debug_info['authentication'] = 'Success';
                // Set session variables
                Session::set('user_id', $user['id']);
                Session::set('username', $user['username']);
                Session::set('role', $user['role']);
                Session::set('email', $user['email']);
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . '/admin/index.php');
                } elseif ($user['role'] === 'formateur') {
                    header('Location: ' . BASE_URL . '/formateur/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid password.';
                $debug_info['authentication'] = 'Failed - Password verification failed';
            }
        } else {
            $error = 'User not found.';
            $debug_info['authentication'] = 'Failed - User not found';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Login Debug</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Login Form</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($debug_info['username']) ? htmlspecialchars($debug_info['username']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($debug_info)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Debug Information</h5>
                </div>
                <div class="card-body">
                    <pre><?php print_r($debug_info); ?></pre>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 