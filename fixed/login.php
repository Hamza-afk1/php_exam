<?php
// Initialize the application
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';
require_once __DIR__ . '/utils/Validator.php';
require_once __DIR__ . '/models/User.php';

// Start the session
Session::init();

// Check if we should force logout (from URL parameter)
if (isset($_GET['force_logout']) && $_GET['force_logout'] == 1) {
    Session::destroy();
}

// Redirect if already logged in
if (Session::isLoggedIn()) {
    $userRole = Session::get('user_role');
    
    switch ($userRole) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
        case 'formateur':
            header('Location: formateur/dashboard.php');
            exit;
        case 'stagiaire':
            header('Location: stagiaire/dashboard.php');
            exit;
        default:
            // If role is not recognized, log them out
            Session::destroy();
            header('Location: login.php');
            exit;
    }
}

$error = '';
$success = '';
$username = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate inputs
    $validator = new Validator($_POST);
    $validation = $validator->validate([
        'username' => 'required',
        'password' => 'required'
    ]);
    
    if ($validation) {
        // Attempt to authenticate user
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            // Set session variables
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('user_email', $user['email']);
            Session::set('user_role', $user['role']);
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    exit;
                case 'formateur':
                    header('Location: formateur/dashboard.php');
                    exit;
                case 'stagiaire':
                    header('Location: stagiaire/dashboard.php');
                    exit;
                default:
                    $error = 'Invalid user role.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Portal - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <link href="assets/css/dark-mode.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #4a5568, #63b3ed);
            font-family: 'Inter', sans-serif;
            transition: background-color 0.5s ease;
        }
        .input-field {
            @apply w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200;
            transition: border-color 0.3s ease;
            
        }
        .input-field:focus {
            border-color: #63b3ed;
            box-shadow: 0 0 0 3px rgba(99, 179, 237, 0.2);
            transform: scale(1.02);
            transition: all 0.3s ease;

        }
        .btn-primary {
            @apply bg-blue-800 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md;
            transition: background-color 0.3s ease;

        }
        .btn-primary:hover {
            background-color: #4facfe;
            transform: scale(1.05);
            color: dark-blue;


        }
        .btn-primary:active {
            transform: scale(0.95);
            background-color: #00f2fe;
            color: dark-blue;
            box-shadow: 0 0 0 3px rgba(0, 242, 254, 0.2);
            transition: all 0.3s ease;

        }
        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 242, 254, 0.2);
            transition: all 0.3s ease;
            color: dark-blue;
            transform: scale(1.02);
            background-color: #00f2fe;


        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            transition: transform 0.3s ease;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .logo {
            width: 100px;
            height: auto;
            transition: transform 0.3s ease;
        }
        .logo:hover {
            transform: scale(1.1);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
            
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .bounce {
            animation: bounce 0.5s;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="absolute top-4 right-4">
        <button id="dark-mode-toggle" class="btn btn-outline-secondary">
            <i class="fas fa-moon"></i> Dark Mode
        </button>
    </div>
    <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-xl shadow-lg fade-in">
        <div class="text-center">
            <img src="https://via.placeholder.com/100" alt="Exam Portal Logo" class="logo mx-auto mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Welcome Back</h2>
            <p class="text-gray-500">Sign in to continue to your dashboard</p>
        </div>
        <form action="" method="post" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <div class="mt-1">
                    <input type="text" name="username" id="username" required 
                        class="input-field border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" 
                        placeholder="Enter your username" value="<?php echo htmlspecialchars($username); ?>">
                </div>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1 relative">
                    <input type="password" name="password" id="password" required 
                        class="input-field border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" 
                        placeholder="Enter your password">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" id="toggle-password" class="text-gray-400 hover:text-blue-500">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" 
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>
                <div class="text-sm">
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                        Forgot your password?
                    </a>
                </div>
            </div>
            <div>
                <button type="submit" class="btn-primary w-full">
                    Sign in
                </button>
            </div>
        </form>
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Don't have an account? 
                <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                    Register here
                </a>
            </p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('toggle-password');

            togglePasswordBtn.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                togglePasswordBtn.querySelector('i').setAttribute('data-feather', type === 'password' ? 'eye' : 'eye-off');
                feather.replace();
            });
        });
    </script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>
