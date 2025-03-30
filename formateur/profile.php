<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../models/User.php';

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

// Get the formateur ID from session
$userId = Session::get('user_id');

// Initialize user model
$userModel = new User();

// Get formateur info
$user = $userModel->getById($userId);
if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

// Process form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Validate input
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $bio = trim($_POST['bio'] ?? '');
        
        // Basic validation
        if (empty($fullName) || empty($email)) {
            $error = 'Name and email are required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email is already in use by another user
            $existingUser = $userModel->findByEmail($email);
            if ($existingUser && $existingUser['id'] != $userId) {
                $error = 'This email is already in use by another account.';
            } else {
                // Update user data
                $userData = [
                    'full_name' => $fullName,
                    'email' => $email,
                    'bio' => $bio
                ];
                
                if ($userModel->update($userData, $userId)) {
                    $message = 'Profile updated successfully!';
                    // Refresh user data
                    $user = $userModel->getById($userId);
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Validate passwords
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            // Verify current password
            if (password_verify($currentPassword, $user['password'])) {
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update the password
                $userData = ['password' => $hashedPassword];
                if ($userModel->update($userData, $userId)) {
                    $message = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-user mr-2"></i> My Profile
    </h1>
    <p class="page-subtitle">View and update your personal information</p>
</div>

<!-- Alerts for success and error messages -->
<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-id-card mr-2"></i> Profile Information
                </h5>
            </div>
            <div class="card-body">
                <form method="post" action="profile.php" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small class="form-text text-muted">Username cannot be changed.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        <div class="invalid-feedback">Please provide your full name.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <div class="invalid-feedback">Please provide a valid email address.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" class="form-control" id="role" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio/About Me</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Share a brief description about yourself or your teaching experience.</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-key mr-2"></i> Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="post" action="profile.php" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <div class="invalid-feedback">Please enter your current password.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">Please confirm your new password.</div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key mr-1"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Account Information -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-info-circle mr-2"></i> Account Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Account Created:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                <p><strong>Last Updated:</strong> <?php echo date('F d, Y', strtotime($user['updated_at'])); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Login Status:</strong> <span class="badge badge-success">Active</span></p>
                <p><strong>Account Type:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
            </div>
        </div>
        
        <div class="mt-3 text-center">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt mr-1"></i> Sign Out
            </a>
        </div>
    </div>
</div>

<script>
    // JavaScript for form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            // Fetch all forms we want to apply validation to
            var forms = document.getElementsByClassName('needs-validation');
            // Loop over them and prevent submission
            Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
            
            // Additional validation for password confirmation
            document.getElementById('confirm_password').addEventListener('input', function() {
                const newPassword = document.getElementById('new_password').value;
                if (this.value !== newPassword) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            document.getElementById('new_password').addEventListener('input', function() {
                const confirmPassword = document.getElementById('confirm_password');
                if (confirmPassword.value !== this.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        }, false);
    })();
</script>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?>
