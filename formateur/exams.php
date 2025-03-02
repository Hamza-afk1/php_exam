<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Question.php';

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

// Initialize models
$userModel = new User();
$examModel = new Exam();
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
            'formateur_id' => $formateurId  // Always use the current formateur's ID
        ];
        
        if (isset($_POST['add_exam'])) {
            // Add new exam
            $result = $examModel->create($examData);
            if ($result) {
                $message = "Exam added successfully!";
                // Redirect to list view
                header('Location: ' . BASE_URL . '/formateur/exams.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to add exam.";
            }
        } else if (isset($_POST['update_exam']) && $examId > 0) {
            // Verify this exam belongs to the current formateur
            $currentExam = $examModel->getById($examId);
            if (!$currentExam || $currentExam['formateur_id'] != $formateurId) {
                $error = "You do not have permission to edit this exam.";
            } else {
                // Update existing exam
                $result = $examModel->update($examData, $examId);
                if ($result) {
                    $message = "Exam updated successfully!";
                    // Redirect to list view
                    header('Location: ' . BASE_URL . '/formateur/exams.php?message=' . urlencode($message));
                    exit;
                } else {
                    $error = "Failed to update exam.";
                }
            }
        }
    } else if (isset($_POST['delete_exam']) && $examId > 0) {
        // Verify this exam belongs to the current formateur
        $currentExam = $examModel->getById($examId);
        if (!$currentExam || $currentExam['formateur_id'] != $formateurId) {
            $error = "You do not have permission to delete this exam.";
        } else {
            // Delete exam
            $result = $examModel->delete($examId);
            if ($result) {
                $message = "Exam deleted successfully!";
                // Redirect to list view
                header('Location: ' . BASE_URL . '/formateur/exams.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to delete exam.";
            }
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
    } else if ($examData['formateur_id'] != $formateurId) {
        $error = "You do not have permission to access this exam.";
        $action = 'list';
    }
}

// Get all exams for this formateur
$exams = $examModel->getExamsByFormateurId($formateurId);

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
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/formateur/exams.php">
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/profile.php">
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
                    <h1 class="h2">Manage Exams</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=add" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Create New Exam
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
                    <!-- Add/Edit Exam Form -->
                    <div class="card">
                        <div class="card-header">
                            <?php echo $action === 'add' ? 'Create New Exam' : 'Edit Exam'; ?>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo BASE_URL; ?>/formateur/exams.php<?php echo $action === 'edit' ? '?action=edit&id=' . $examId : ''; ?>">
                                <div class="form-group">
                                    <label for="name">Exam Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                        value="<?php echo $action === 'edit' ? htmlspecialchars($examData['name']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $action === 'edit' ? htmlspecialchars($examData['description']) : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="time_limit">Time Limit (in minutes)</label>
                                    <input type="number" class="form-control" id="time_limit" name="time_limit" required min="1" max="240"
                                        value="<?php echo $action === 'edit' ? (int)$examData['time_limit'] : '60'; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="passing_score">Passing Score (%)</label>
                                    <input type="number" class="form-control" id="passing_score" name="passing_score" required min="1" max="100"
                                        value="<?php echo $action === 'edit' ? (isset($examData['passing_score']) ? (int)$examData['passing_score'] : 60) : '60'; ?>">
                                </div>
                                <button type="submit" name="<?php echo $action === 'add' ? 'add_exam' : 'update_exam'; ?>" class="btn btn-primary">
                                    <?php echo $action === 'add' ? 'Create Exam' : 'Update Exam'; ?>
                                </button>
                                <a href="<?php echo BASE_URL; ?>/formateur/exams.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php elseif ($action === 'view'): ?>
                    <!-- View Exam Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4><?php echo htmlspecialchars($examData['name']); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($examData['description']); ?></p>
                                    <p><strong>Time Limit:</strong> <?php echo (int)$examData['time_limit']; ?> minutes</p>
                                    <p><strong>Passing Score:</strong> <?php echo isset($examData['passing_score']) ? (int)$examData['passing_score'] : 60; ?>%</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Created At:</strong> <?php echo date('Y-m-d H:i', strtotime($examData['created_at'])); ?></p>
                                    
                                    <div class="mt-3">
                                        <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=edit&id=<?php echo $examId; ?>" class="btn btn-info">
                                            <i class="fas fa-edit"></i> Edit Exam
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/formateur/questions.php?exam_id=<?php echo $examId; ?>" class="btn btn-primary">
                                            <i class="fas fa-question-circle"></i> Manage Questions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Question List for this exam would go here -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Exam Questions</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <a href="<?php echo BASE_URL; ?>/formateur/questions.php?action=add&exam_id=<?php echo $examId; ?>" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Add New Question
                                </a>
                            </div>
                            
                            <div class="alert alert-info">
                                This section would display all questions for this exam.
                                Complete the questions management functionality in the questions.php file.
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Exam List Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Exam Name</th>
                                    <th>Time Limit</th>
                                    <th>Passing Score</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($exams)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No exams found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($exams as $exam): ?>
                                        <tr>
                                            <td><?php echo $exam['id']; ?></td>
                                            <td><?php echo htmlspecialchars($exam['name']); ?></td>
                                            <td><?php echo (int)$exam['time_limit']; ?> minutes</td>
                                            <td><?php echo (int)$exam['passing_score']; ?>%</td>
                                            <td><?php echo date('Y-m-d', strtotime($exam['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=view&id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=edit&id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form class="d-inline" method="post" action="<?php echo BASE_URL; ?>/formateur/exams.php?id=<?php echo $exam['id']; ?>" 
                                                      onsubmit="return confirm('Are you sure you want to delete this exam?');">
                                                    <button type="submit" name="delete_exam" class="btn btn-sm btn-danger">
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
</body>
</html>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
