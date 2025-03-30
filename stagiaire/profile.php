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
require_once __DIR__ . '/../models/Result.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is stagiaire
if (Session::get('user_role') !== 'stagiaire') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Get the stagiaire ID from session
$stagiaireId = Session::get('user_id');

// Initialize models
$userModel = new User();
$resultModel = new Result();

// Get user data
$user = $userModel->getById($stagiaireId);
if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

// Get performance statistics
try {
    $results = $resultModel->getAllStagiaireResults($stagiaireId);
    $totalExams = count($results);
    $totalScore = 0;
    $passedExams = 0;
    
    foreach ($results as $result) {
        $totalScore += $result['score'];
        
        // Get exam details to check if passed
        $exam = $examModel->getById($result['exam_id']);
        $passingScore = $exam ? $exam['passing_score'] : 70;
        
        if ($result['score'] >= $passingScore) {
            $passedExams++;
        }
    }
    
    $averageScore = $totalExams > 0 ? round($totalScore / $totalExams, 1) : 0;
    $passRate = $totalExams > 0 ? round(($passedExams / $totalExams) * 100, 1) : 0;
} catch (Exception $e) {
    // Handle the error gracefully
    $totalExams = 0;
    $averageScore = 0;
    $passRate = 0;
}

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $userData = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'full_name' => trim($_POST['full_name']),
        'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : null,
        'address' => isset($_POST['address']) ? trim($_POST['address']) : null,
        'bio' => isset($_POST['bio']) ? trim($_POST['bio']) : null
    ];
    
    $validator = new Validator();
    
    if (!$validator->email($userData['email'])) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($userData['username']) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (strlen($userData['full_name']) < 2) {
        $error = 'Full name is required.';
    } else {
        
        // Check if username is already in use by another user
        $existingUser = $userModel->getByUsername($userData['username']);
        if ($existingUser && $existingUser['id'] != $stagiaireId) {
            $error = 'Username is already taken.';
        } else {
            // Check if email is already in use by another user
            $existingUser = $userModel->getByEmail($userData['email']);
            if ($existingUser && $existingUser['id'] != $stagiaireId) {
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
                    $result = $userModel->update($userData, $stagiaireId);
                    if ($result) {
                        // Update session data
                        Session::set('username', $userData['username']);
                        Session::set('user_email', $userData['email']);
                        
                        $message = 'Profile updated successfully.';
                        
                        // Get updated user data
                        $user = $userModel->getById($stagiaireId);
                    } else {
                        $error = 'Failed to update profile.';
                    }
                }
            }
        }
    }
}

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

<div id="mainContent" class="main-content">
    <div class="content-header">
        <h1><i class="fas fa-user"></i> My Profile</h1>
        <p>View and manage your profile information</p>
                </div>
                
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="background-color: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid #dc3545; font-weight: 500;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
                <?php endif; ?>
                
    <?php if (!empty($message)): ?>
        <div class="alert alert-success" style="background-color: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid #28a745; font-weight: 500;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
                <?php endif; ?>
                
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card"></i> Profile Summary</h5>
                                    </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name'] ?? $user['username']); ?>&background=0D8ABC&color=fff&size=128" class="rounded-circle img-thumbnail profile-image" alt="Profile Image">
                    </div>
                    <h4><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p><small>Member since: <?php echo date('F Y', strtotime($user['created_at'])); ?></small></p>
                                    </div>
                                </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Performance Summary</h5>
                            </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="mb-2"><i class="fas fa-clipboard-list fa-2x text-info"></i></div>
                            <div class="h3"><?php echo $totalExams; ?></div>
                            <div class="small text-muted">Exams</div>
                        </div>
                        <div class="col-4">
                            <div class="mb-2"><i class="fas fa-star fa-2x text-warning"></i></div>
                            <div class="h3"><?php echo $averageScore; ?>%</div>
                            <div class="small text-muted">Avg. Score</div>
                    </div>
                        <div class="col-4">
                            <div class="mb-2"><i class="fas fa-trophy fa-2x text-success"></i></div>
                            <div class="h3"><?php echo $passRate; ?>%</div>
                            <div class="small text-muted">Pass Rate</div>
                        </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Contact Information</h5>
                </div>
                            <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted">Phone</div>
                        <div><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></div>
                                    </div>
                    <div class="mb-3">
                        <div class="small text-muted">Address</div>
                        <div><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
        <div class="col-md-8">
                        <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Profile</h5>
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
    </div>

<?php
// Include footer
require_once __DIR__ . '/includes/footer_fixed.php';
?>
