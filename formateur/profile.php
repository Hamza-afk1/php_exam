<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../models/User.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is formateur
if (Session::get('user_role') !== 'formateur') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Get the formateur ID from session
$formateurId = Session::get('user_id');

// Initialize User model
$userModel = new User();

// Get user data
$user = $userModel->getById($formateurId);
if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $validator = new Validator($_POST);
    $validation = $validator->validate([
        'username' => 'required|min:3',
        'email' => 'required|email',
        'full_name' => 'required'
    ]);
    
    if (!$validation) {
        $error = 'Please fill all required fields correctly.';
    } else {
        $userData = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'bio' => $_POST['bio'] ?? ''
        ];
        
        // Check if username is already in use by another user
        $existingUser = $userModel->getByUsername($userData['username']);
        if ($existingUser && $existingUser['id'] != $formateurId) {
            $error = 'Username is already taken.';
        } else {
            // Check if email is already in use by another user
            $existingUser = $userModel->getByEmail($userData['email']);
            if ($existingUser && $existingUser['id'] != $formateurId) {
                $error = 'Email is already registered.';
            } else {
                // Process password change if both password fields are filled
                if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
                    if ($_POST['password'] === $_POST['confirm_password']) {
                        if (strlen($_POST['password']) < 6) {
                            $error = 'Password must be at least 6 characters long.';
                        } else {
                            $userData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        }
                    } else {
                        $error = 'Passwords do not match.';
                    }
                }
                
                // Update user data if no errors
                if (empty($error)) {
                    $result = $userModel->update($userData, $formateurId);
                    if ($result) {
                        // Update session data
                        Session::set('username', $userData['username']);
                        Session::set('user_email', $userData['email']);
                        
                        $message = 'Profile updated successfully.';
                        
                        // Get updated user data
                        $user = $userModel->getById($formateurId);
                    } else {
                        $error = 'Failed to update profile.';
                    }
                }
            }
        }
    }
}

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-size: .875rem;
            padding-top: 4.5rem;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #007bff;
            background-color:rgb(189, 188, 188);
            border-radius: 0.5rem;
            
        }
        .profile-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/formateur/dashboard.php"><?php echo SITE_NAME; ?></a>
        <ul class="navbar-nav px-3 ml-auto">
            <li class="nav-item text-nowrap mr-3">
                <button id="dark-mode-toggle" class="btn btn-outline-light">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/exams.php">
                                <i class="fas fa-clipboard-list"></i> My Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/questions.php">
                                <i class="fas fa-question-circle"></i> Questions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/formateur/profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="sidebar-footer mt-auto position-absolute" style="bottom: 20px; width: 100%;">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php" style="padding: 0.75rem 1rem;">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Profile</h1>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-4 col-xl-3">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name'] ?? $user['username']); ?>&size=200&background=007bff&color=fff" class="profile-image mb-3" alt="Profile Image">
                                <h5 class="my-3"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h5>
                                <p class="text-muted mb-1"><?php echo ucfirst($user['role']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                
                                <div class="d-flex justify-content-center mt-3">
                                    <?php if (!empty($user['phone'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($user['phone']); ?>" class="btn btn-outline-primary mx-1">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="btn btn-outline-primary mx-1">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <p class="mb-0 font-weight-bold">Username</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($user['username']); ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <p class="mb-0 font-weight-bold">Role</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <p class="text-muted mb-0"><?php echo ucfirst($user['role']); ?></p>
                                    </div>
                                </div>
                                <?php if (!empty($user['phone'])): ?>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <p class="mb-0 font-weight-bold">Phone</p>
                                        </div>
                                        <div class="col-sm-8">
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($user['phone']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($user['address'])): ?>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <p class="mb-0 font-weight-bold">Address</p>
                                        </div>
                                        <div class="col-sm-8">
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($user['address']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <p class="mb-0 font-weight-bold">Created</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <p class="text-muted mb-0"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8 col-xl-9">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="full_name">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="bio">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <h5 class="mt-4 mb-3">Change Password</h5>
                                    <div class="form-group">
                                        <label for="password">New Password</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                                        <small class="form-text text-muted">At least 6 characters long.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
