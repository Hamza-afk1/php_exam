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
if (Session::get('role') !== 'formateur') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Get the formateur ID from session
$formateurId = Session::get('user_id');

// Initialize models
$userModel = new User();
$examModel = new Exam();
$questionModel = new Question();

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
            'time_limit' => $_POST['time_limit'],
            'formateur_id' => $formateurId
        ];
        
        if (isset($_POST['add_exam'])) {
            // Add new exam
            if ($examModel->create($examData)) {
                $message = "Exam added successfully!";
                // Redirect to list view to avoid resubmission
                header('Location: ' . BASE_URL . '/formateur/exams.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to add exam!";
            }
        } else if (isset($_POST['update_exam'])) {
            // Update existing exam
            $examId = (int)$_POST['exam_id'];
            if ($examModel->update($examData, $examId)) {
                $message = "Exam updated successfully!";
                header('Location: ' . BASE_URL . '/formateur/exams.php?message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to update exam!";
            }
        }
    } else if (isset($_POST['delete_exam'])) {
        // Delete exam
        $examId = (int)$_POST['exam_id'];
        if ($examModel->delete($examId)) {
            $message = "Exam deleted successfully!";
            header('Location: ' . BASE_URL . '/formateur/exams.php?message=' . urlencode($message));
            exit;
        } else {
            $error = "Failed to delete exam!";
        }
    }
}

// Handle URL message and error parameters
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-list mr-2"></i> My Exams
                </h1>
                <p class="page-subtitle">Create and manage your examination materials</p>
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

            <?php if ($action === 'list'): ?>
                <!-- Exams List View -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list mr-2"></i> All Exams
                        </h5>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus-circle mr-1"></i> Create New Exam
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get all exams for this formateur
                        $exams = $examModel->getExamsByFormateurId($formateurId);
                        
                        if (empty($exams)):
                        ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list empty-state-icon"></i>
                                <p class="empty-state-text">You haven't created any exams yet.</p>
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus-circle mr-1"></i> Create Your First Exam
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Time Limit</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exams as $exam): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exam['name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($exam['description'], 0, 50)) . (strlen($exam['description']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo $exam['time_limit']; ?> min</td>
                                                <td><?php echo date('M d, Y', strtotime($exam['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="?action=edit&id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info" title="Manage Questions">
                                                            <i class="fas fa-question-circle"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                data-toggle="modal" 
                                                                data-target="#deleteExamModal" 
                                                                data-exam-id="<?php echo $exam['id']; ?>"
                                                                data-exam-name="<?php echo htmlspecialchars($exam['name']); ?>"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Exam Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php if ($action === 'add'): ?>
                                <i class="fas fa-plus-circle mr-2"></i> Create New Exam
                            <?php else: ?>
                                <i class="fas fa-edit mr-2"></i> Edit Exam
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // If editing, get the exam data
                        $examData = [];
                        if ($action === 'edit' && $examId > 0) {
                            $examData = $examModel->getById($examId);
                            if (!$examData || $examData['formateur_id'] != $formateurId) {
                                echo '<div class="alert alert-danger">Exam not found or you do not have permission to edit it.</div>';
                                echo '<div class="text-center mt-3"><a href="exams.php" class="btn btn-primary">Back to Exams</a></div>';
                                require_once __DIR__ . '/includes/footer_fixed.php';
                                exit;
                            }
                        }
                        ?>
                        <form method="post" action="exams.php" class="needs-validation" novalidate>
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="name">Exam Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                    value="<?php echo $action === 'edit' ? htmlspecialchars($examData['name']) : ''; ?>" required>
                                <div class="invalid-feedback">Please provide an exam name.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $action === 'edit' ? htmlspecialchars($examData['description']) : ''; ?></textarea>
                                <div class="invalid-feedback">Please provide a description.</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="time_limit">Time Limit (minutes)</label>
                                    <input type="number" class="form-control" id="time_limit" name="time_limit" 
                                        value="<?php echo $action === 'edit' ? $examData['time_limit'] : '60'; ?>" min="1" required>
                                    <div class="invalid-feedback">Please provide a valid time limit.</div>
                                </div>
                            </div>
                            
                            <div class="form-group text-center mt-4">
                                <a href="exams.php" class="btn btn-secondary mr-2">
                                    <i class="fas fa-arrow-left mr-1"></i> Cancel
                                </a>
                                <?php if ($action === 'add'): ?>
                                    <button type="submit" name="add_exam" class="btn btn-primary">
                                        <i class="fas fa-plus-circle mr-1"></i> Create Exam
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="update_exam" class="btn btn-success">
                                        <i class="fas fa-save mr-1"></i> Save Changes
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

<!-- Delete Exam Modal -->
<div class="modal fade" id="deleteExamModal" tabindex="-1" role="dialog" aria-labelledby="deleteExamModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteExamModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the exam "<span id="examNameToDelete"></span>"?</p>
                <p class="text-danger">This action cannot be undone. All questions and results associated with this exam will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="post" action="exams.php" id="deleteExamForm">
                    <input type="hidden" name="exam_id" id="examIdToDelete" value="">
                    <button type="submit" name="delete_exam" class="btn btn-danger">Delete</button>
                </form>
            </div>
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
        }, false);
    })();
    
    // JavaScript for delete modal
    $('#deleteExamModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var examId = button.data('exam-id');
        var examName = button.data('exam-name');
        var modal = $(this);
        modal.find('#examNameToDelete').text(examName);
        modal.find('#examIdToDelete').val(examId);
    });
</script>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?>
