<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../models/User.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is admin
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Initialize User model
$userModel = new User();

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle user actions (add, edit, delete)
    if (isset($_POST['add_user']) || isset($_POST['update_user'])) {
        $userData = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'role' => $_POST['role']
        ];
        
        // Add password for new users or if password field is filled for updates
        if (isset($_POST['add_user']) || (!empty($_POST['password']))) {
            $userData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($_POST['add_user'])) {
            // Add new user
            $result = $userModel->create($userData);
            if ($result) {
                $message = "User added successfully!";
                // Redirect to list view
                header('Location: ' . BASE_URL . '/admin/users.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to add user.";
            }
        } else if (isset($_POST['update_user']) && $userId > 0) {
            // Update existing user
            $result = $userModel->update($userData, $userId);
            if ($result) {
                $message = "User updated successfully!";
                // Redirect to list view
                header('Location: ' . BASE_URL . '/admin/users.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to update user.";
            }
        }
    } else if (isset($_POST['delete_user']) && $userId > 0) {
        // Delete user
        $result = $userModel->delete($userId);
        if ($result) {
            $message = "User deleted successfully!";
            // Redirect to list view
            header('Location: ' . BASE_URL . '/admin/users.php?message=' . urlencode($message));
            exit;
        } else {
            $error = "Failed to delete user.";
        }
    }
}

// Get message from query string (for redirects)
if (empty($message) && isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get user data for edit form
$userData = null;
if ($action === 'edit' && $userId > 0) {
    $userData = $userModel->getById($userId);
    if (!$userData) {
        $error = "User not found.";
        $action = 'list';
    }
}

// Get user list filtered by role if specified
$users = [];
if ($role) {
    $users = $userModel->getUsersByRole($role);
} else {
    $users = $userModel->getAll();
}

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dark-mode.css" rel="stylesheet">
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
    </style>
</head>
<body class="admin-page bg-gray-100">
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin/users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/exams.php">
                                <i class="fas fa-clipboard-list"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/settings.php">
                                <i class="fas fa-cog"></i> Settings
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
                    <h1 class="h2">Manage Users</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo BASE_URL; ?>/admin/users.php?action=add" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add New User
                        </a>
                    </div>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <!-- Add/Edit User Form -->
                    <div class="card">
                        <div class="card-header">
                            <?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/users.php<?php echo $action === 'edit' ? '?action=edit&id=' . $userId : ''; ?>">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                        value="<?php echo $action === 'edit' ? htmlspecialchars($userData['username']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                        value="<?php echo $action === 'edit' ? htmlspecialchars($userData['email']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="password"><?php echo $action === 'add' ? 'Password' : 'New Password (leave blank to keep current)'; ?></label>
                                    <input type="password" class="form-control" id="password" name="password" <?php echo $action === 'add' ? 'required' : ''; ?>>
                                </div>
                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="admin" <?php echo ($action === 'edit' && $userData['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="formateur" <?php echo ($action === 'edit' && $userData['role'] === 'formateur') ? 'selected' : ''; ?>>Formateur</option>
                                        <option value="stagiaire" <?php echo ($action === 'edit' && $userData['role'] === 'stagiaire') ? 'selected' : ''; ?>>Stagiaire</option>
                                    </select>
                                </div>
                                <button type="submit" name="<?php echo $action === 'add' ? 'add_user' : 'update_user'; ?>" class="btn btn-primary">
                                    <?php echo $action === 'add' ? 'Add User' : 'Update User'; ?>
                                </button>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Filter Controls -->
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-outline-secondary <?php echo $role === '' ? 'active' : ''; ?>">All Users</a>
                            <a href="<?php echo BASE_URL; ?>/admin/users.php?role=admin" class="btn btn-outline-secondary <?php echo $role === 'admin' ? 'active' : ''; ?>">Admins</a>
                            <a href="<?php echo BASE_URL; ?>/admin/users.php?role=formateur" class="btn btn-outline-secondary <?php echo $role === 'formateur' ? 'active' : ''; ?>">Formateurs</a>
                            <a href="<?php echo BASE_URL; ?>/admin/users.php?role=stagiaire" class="btn btn-outline-secondary <?php echo $role === 'stagiaire' ? 'active' : ''; ?>">Stagiaires</a>
                        </div>
                    </div>
                    
                    <!-- User List Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No users found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $user['role'] === 'admin' ? 'danger' : 
                                                        ($user['role'] === 'formateur' ? 'primary' : 'success'); 
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/admin/users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form class="d-inline" method="post" action="<?php echo BASE_URL; ?>/admin/users.php?id=<?php echo $user['id']; ?>" 
                                                      onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
