<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';
require_once __DIR__ . '/models/User.php';

// Initialize session
Session::init();

// Check if user is already logged in
if (Session::isLoggedIn()) {
    $role = Session::get('role');
    error_log("User already logged in with role: " . $role);
    
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . '/admin/index.php');
    } elseif ($role === 'formateur') {
        header('Location: ' . BASE_URL . '/formateur/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
    }
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $error = '';

    error_log("Login attempt for username: " . $username);

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
        error_log("Login failed: Empty username or password");
    } else {
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);

        if ($user) {
            error_log("Login successful for username: " . $username . " with role: " . $user['role']);
            
            // Set session variables
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('role', $user['role']);
            Session::set('email', $user['email']);
            
            // For debugging: log session variables
            error_log("Set session variables: user_id=" . Session::get('user_id') . 
                      ", username=" . Session::get('username') . 
                      ", role=" . Session::get('role'));
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                error_log("Redirecting admin to: " . BASE_URL . '/admin/index.php');
                header('Location: ' . BASE_URL . '/admin/index.php');
            } elseif ($user['role'] === 'formateur') {
                error_log("Redirecting formateur to: " . BASE_URL . '/formateur/dashboard.php');
                header('Location: ' . BASE_URL . '/formateur/dashboard.php');
            } else {
                error_log("Redirecting stagiaire to: " . BASE_URL . '/stagiaire/dashboard.php');
                header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
            error_log("Login failed: Invalid credentials for username: " . $username);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="color-scheme" content="light dark">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" as="style">
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Inter (Apple-like font) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/dark-mode.css" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #f5f5f7;
            --bg-secondary: #ffffff;
            --text-primary: #1d1d1f;
            --text-secondary: #86868b;
            --primary-color: #007aff;
            --primary-color-hover: #0062cc;
            --border-color: rgba(0, 0, 0, 0.1);
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            --btn-shadow: 0 2px 5px rgba(0, 98, 204, 0.3);
            --input-bg: #ffffff;
            --input-text: #1d1d1f;
            --card-bg: #ffffff;
        }
        
        body.dark-mode {
            --bg-primary: #1d1d1f;
            --bg-secondary: #2c2c2e;
            --text-primary: #f5f5f7;
            --text-secondary: #98989d;
            --border-color: #3a3a3c;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            --input-bg: #3a3a3c;
            --input-text: #f5f5f7;
            --card-bg: #2c2c2e;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .auth-wrapper {
            width: 100%;
            max-width: 400px;
        }
        
        .auth-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            padding: 30px;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-title {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--input-bg);
            color: var(--input-text);
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.3s ease, color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
            outline: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 500;
            margin-top: 5px;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color-hover);
            border-color: var(--primary-color-hover);
            transform: translateY(-1px);
            box-shadow: var(--btn-shadow);
        }
        
        .btn-outline-secondary {
            color: var(--text-secondary);
            border-color: var(--border-color);
            background-color: transparent;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: rgba(128, 128, 128, 0.1);
        }
        
        label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            transition: color 0.3s ease;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .alert-danger {
            background-color: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
        }
        
        .version-info {
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 15px;
            transition: color 0.3s ease;
        }
        
        .fix-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            font-size: 0.85rem;
            color: var(--primary-color);
            transition: color 0.3s ease;
        }
        
        .fix-link:hover {
            text-decoration: none;
            opacity: 0.8;
        }
        
        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bg-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .loading.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid rgba(0, 122, 255, 0.1);
            border-top-color: var(--primary-color);
            animation: spin 1s infinite linear;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <!-- Loading Animation -->
    <div class="loading">
        <div class="spinner"></div>
    </div>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h4 class="card-title">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </h4>
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            <div class="text-center mt-3">
                <button id="dark-mode-toggle" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
            </div>
            <div class="version-info">
                <?php echo SITE_NAME; ?> v1.0 &copy; <?php echo date('Y'); ?>
            </div>
            <a href="<?php echo BASE_URL; ?>/fix_admin_login.php" class="fix-link">Having trouble? Run Setup</a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <!-- Bootstrap Bundle (includes Popper.js) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dark Mode JavaScript -->
    <script>
        // Dark Mode Manager
        class DarkMode {
            constructor() {
                this.init();
            }
            
            init() {
                // Get elements
                this.body = document.body;
                this.darkModeToggle = document.getElementById('dark-mode-toggle');
                this.metaThemeColor = document.querySelector('meta[name="theme-color"]');
                this.toggleIcon = this.darkModeToggle.querySelector('i');
                
                // Check for saved theme preference or use device preference
                const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const savedTheme = localStorage.getItem('theme');
                
                // Apply theme
                if (savedTheme === 'dark' || (!savedTheme && prefersDarkMode)) {
                    this.enableDarkMode(false);
                }
                
                // Add event listeners
                this.addEventListeners();
            }
            
            addEventListeners() {
                // Toggle theme when button is clicked
                if (this.darkModeToggle) {
                    this.darkModeToggle.addEventListener('click', () => {
                        if (this.body.classList.contains('dark-mode')) {
                            this.disableDarkMode();
                        } else {
                            this.enableDarkMode();
                        }
                    });
                }
                
                // Listen for changes in system preference
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                    if (!localStorage.getItem('theme')) {
                        if (e.matches) {
                            this.enableDarkMode(false);
                        } else {
                            this.disableDarkMode(false);
                        }
                    }
                });
            }
            
            enableDarkMode(savePreference = true) {
                this.body.classList.add('dark-mode');
                if (this.toggleIcon) {
                    this.toggleIcon.classList.remove('fa-moon');
                    this.toggleIcon.classList.add('fa-sun');
                    this.darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                }
                if (this.metaThemeColor) {
                    this.metaThemeColor.setAttribute('content', '#1d1d1f');
                }
                if (savePreference) localStorage.setItem('theme', 'dark');
            }
            
            disableDarkMode(savePreference = true) {
                this.body.classList.remove('dark-mode');
                if (this.toggleIcon) {
                    this.toggleIcon.classList.remove('fa-sun');
                    this.toggleIcon.classList.add('fa-moon');
                    this.darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
                }
                if (this.metaThemeColor) {
                    this.metaThemeColor.setAttribute('content', '#FFFFFF');
                }
                if (savePreference) localStorage.setItem('theme', 'light');
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new DarkMode();
            
            // Loading animation
            const loading = document.querySelector('.loading');
            if (loading) {
                loading.classList.add('hidden');
                setTimeout(() => {
                    loading.style.display = 'none';
                }, 500);
            }
        });
    </script>
</body>
</html> 