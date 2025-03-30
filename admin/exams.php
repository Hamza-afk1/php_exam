<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Question.php';

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

// Initialize models
$examModel = new Exam();
$userModel = new User();

// We'll assume there's a Question model - if not, commented out
// $questionModel = new Question();

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$examId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle exam actions (add, edit, delete)
    if (isset($_POST['add_exam']) || isset($_POST['update_exam'])) {
        $examData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'time_limit' => (int)$_POST['time_limit'],
            'passing_score' => (int)$_POST['passing_score'],
            'formateur_id' => (int)$_POST['formateur_id']
        ];
        
        if (isset($_POST['add_exam'])) {
            // Add new exam
            $result = $examModel->create($examData);
            if ($result) {
                $message = "Exam added successfully!";
                // Redirect to list view
                header('Location: ' . BASE_URL . '/admin/exams.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to add exam.";
            }
        } else if (isset($_POST['update_exam']) && $examId > 0) {
            // Update existing exam
            $result = $examModel->update($examData, $examId);
            if ($result) {
                $message = "Exam updated successfully!";
                // Redirect to list view
                header('Location: ' . BASE_URL . '/admin/exams.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to update exam.";
            }
        }
    } else if (isset($_POST['delete_exam']) && $examId > 0) {
        // Delete exam
        $result = $examModel->delete($examId);
        if ($result) {
            $message = "Exam deleted successfully!";
            // Redirect to list view
            header('Location: ' . BASE_URL . '/admin/exams.php?message=' . urlencode($message));
            exit;
        } else {
            $error = "Failed to delete exam.";
        }
    }
}

// Get message from query string (for redirects)
if (empty($message) && isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get exam data for edit form
$examData = null;
if (($action === 'edit' || $action === 'view') && $examId > 0) {
    $examData = $examModel->getById($examId);
    if (!$examData) {
        $error = "Exam not found.";
        $action = 'list';
    }
}

// Get formateurs for dropdown
$formateurs = $userModel->getUsersByRole('formateur');

// Get all exams
$exams = $examModel->getAll();

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin-theme.css" rel="stylesheet">
    <link href="../assets/css/dark-mode.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/admin/index.php">
            <i class="fas fa-graduation-cap mr-2"></i><?php echo SITE_NAME; ?>
        </a>
        <div class="ml-auto">
            <button id="dark-mode-toggle" class="btn btn-outline-secondary">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/index.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin/exams.php">
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
                <div class="sidebar-footer">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="page-header d-flex justify-content-between align-items-center pt-3">
                    <h1>Manage Exams</h1>
                    <div class="btn-toolbar">
                        <a href="<?php echo BASE_URL; ?>/admin/exams.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Exam
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Time Limit</th>
                                            <th>Passing Score</th>
                                            <th>Formateur</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exams as $exam): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exam['name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($exam['description'], 0, 100)) . '...'; ?></td>
                                                <td><?php echo (int)$exam['time_limit']; ?> min</td>
                                                <td><?php echo (int)$exam['passing_score']; ?>%</td>
                                                <td>
                                                    <?php 
                                                        $formateur = array_filter($formateurs, function($f) use ($exam) {
                                                            return $f['id'] == $exam['formateur_id'];
                                                        });
                                                        $formateur = reset($formateur);
                                                        echo $formateur ? htmlspecialchars($formateur['username']) : 'N/A';
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo BASE_URL; ?>/admin/exams.php?action=edit&id=<?php echo $exam['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>/admin/questions.php?exam_id=<?php echo $exam['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-list"></i>
                                                        </a>
                                                        <form method="post" action="<?php echo BASE_URL; ?>/admin/exams.php?action=delete&id=<?php echo $exam['id']; ?>" 
                                                              class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exam?');">
                                                            <button type="submit" name="delete_exam" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo $action === 'add' ? 'Add New Exam' : 'Edit Exam'; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/exams.php<?php echo $action === 'edit' ? '?action=edit&id=' . $examId : ''; ?>">
                                <div class="form-group">
                                    <label for="name">Exam Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           value="<?php echo $action === 'edit' ? htmlspecialchars($examData['name']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $action === 'edit' ? htmlspecialchars($examData['description']) : ''; ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="time_limit">Time Limit (minutes)</label>
                                            <input type="number" class="form-control" id="time_limit" name="time_limit" required 
                                                   min="1" max="240" value="<?php echo $action === 'edit' ? (int)$examData['time_limit'] : '60'; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="passing_score">Passing Score (%)</label>
                                            <input type="number" class="form-control" id="passing_score" name="passing_score" required 
                                                   min="1" max="100" value="<?php echo $action === 'edit' ? (int)$examData['passing_score'] : '70'; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="formateur_id">Assigned Formateur</label>
                                    <select class="form-control" id="formateur_id" name="formateur_id" required>
                                        <option value="">Select Formateur</option>
                                        <?php foreach ($formateurs as $formateur): ?>
                                            <option value="<?php echo $formateur['id']; ?>" 
                                                    <?php echo ($action === 'edit' && $examData['formateur_id'] == $formateur['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($formateur['username']) . ' (' . htmlspecialchars($formateur['email']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <button type="submit" name="<?php echo $action === 'add' ? 'add_exam' : 'update_exam'; ?>" class="btn btn-primary">
                                        <?php echo $action === 'add' ? 'Add Exam' : 'Update Exam'; ?>
                                    </button>
                                    <a href="<?php echo BASE_URL; ?>/admin/exams.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
</body>
</html>