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
if (Session::get('role') !== 'admin') {
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
            $userData['password'] = $_POST['password']; // Don't hash here, let the User model do it
            // Add debug log
            error_log("Creating user with username: " . $userData['username'] . ", password (unhashed): " . substr($userData['password'], 0, 3) . "...");
        }
        
        if (isset($_POST['add_user'])) {
            // Add new user
            $result = $userModel->create($userData);
            if ($result) {
                $message = "User added successfully!";
                error_log("User created successfully with ID: " . $result);
                // Redirect to list view
                header('Location: ' . BASE_URL . '/admin/users.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to add user.";
                error_log("Failed to create user: " . $userData['username']);
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

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Main Content -->
<div class="welcome-card">
    <h1 class="welcome-title">Manage Users</h1>
    <p class="welcome-date">Organize and manage system users</p>
</div>

<?php if (!empty($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($error); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>User List</span>
                <div>
                    <a href="?role=admin" class="btn btn-sm <?php echo $role === 'admin' ? 'btn-primary' : 'btn-outline-secondary'; ?> mr-1">
                        Admins
                    </a>
                    <a href="?role=formateur" class="btn btn-sm <?php echo $role === 'formateur' ? 'btn-primary' : 'btn-outline-secondary'; ?> mr-1">
                        Formateurs
                    </a>
                    <a href="?role=stagiaire" class="btn btn-sm <?php echo $role === 'stagiaire' ? 'btn-primary' : 'btn-outline-secondary'; ?> mr-1">
                        Stagiaires
                    </a>
                    <a href="?" class="btn btn-sm <?php echo empty($role) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        All
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge <?php
                                                if ($user['role'] === 'admin') echo 'badge-danger';
                                                elseif ($user['role'] === 'formateur') echo 'badge-primary';
                                                else echo 'badge-success';
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary mr-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="post" action="?action=delete&id=<?php echo $user['id']; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <?php echo ($action === 'edit') ? 'Edit User' : 'Add New User'; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo ($action === 'edit') ? '?action=edit&id=' . $userId : '?action=add'; ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo ($userData) ? htmlspecialchars($userData['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo ($userData) ? htmlspecialchars($userData['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php echo ($action === 'edit') ? 'Password (leave blank to keep current)' : 'Password'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo ($action === 'add') ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="admin" <?php echo ($userData && $userData['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="formateur" <?php echo ($userData && $userData['role'] === 'formateur') ? 'selected' : ''; ?>>Formateur</option>
                            <option value="stagiaire" <?php echo ($userData && $userData['role'] === 'stagiaire') ? 'selected' : ''; ?>>Stagiaire</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-0">
                        <?php if ($action === 'edit'): ?>
                            <button type="submit" name="update_user" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Update User
                            </button>
                            <a href="?" class="btn btn-light">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Add User
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
