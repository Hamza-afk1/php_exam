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
$questionModel = new Question();

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$questionId = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;

// Initialize variables
$message = '';
$error = '';
$examData = null;
$questions = [];

// Verify this exam belongs to the current formateur if an exam ID is provided
if ($examId > 0) {
    $examData = $examModel->getById($examId);
    if (!$examData) {
        $error = "Exam not found.";
    } else if ($examData['formateur_id'] != $formateurId) {
        $error = "You do not have permission to access this exam.";
    } else {
        // Get questions for this exam
        $questions = $questionModel->getQuestionsByExam($examId);
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_question']) || isset($_POST['update_question'])) {
        $questionData = [
            'exam_id' => $examId,
            'question_type' => $_POST['question_type'],
            'question_text' => $_POST['question_text'],
            'points' => (int)$_POST['points'] ?: 1
        ];
        
        // Handle correct answers differently based on question type
        if ($_POST['question_type'] === 'qcm') {
            // For QCM, handle multiple possible correct answers as an array
            if (isset($_POST['correct_answer']) && is_array($_POST['correct_answer']) && !empty($_POST['correct_answer'])) {
                $questionData['correct_answer'] = json_encode($_POST['correct_answer']);
            } else {
                $error = "Please select at least one correct answer for the multiple choice question.";
            }
        } else {
            // For open questions, use the text field
            $questionData['correct_answer'] = $_POST['correct_answer'];
        }
        
        // Handle options for QCM question type
        if ($_POST['question_type'] === 'qcm') {
            $options = [];
            foreach ($_POST['option'] as $index => $option) {
                if (!empty($option)) {
                    $options[] = $option;
                }
            }
            $questionData['options'] = $options;
        }
        
        if (isset($_POST['add_question'])) {
            // Add new question
            $result = $questionModel->create($questionData);
            if ($result) {
                $message = "Question added successfully!";
                // Reload the page to display updated list
                header('Location: ' . BASE_URL . '/formateur/questions.php?exam_id=' . $examId . '&message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to add question.";
            }
        } else if (isset($_POST['update_question']) && $questionId > 0) {
            // Update existing question
            $result = $questionModel->update($questionData, $questionId);
            if ($result) {
                $message = "Question updated successfully!";
                // Reload the page to display updated list
                header('Location: ' . BASE_URL . '/formateur/questions.php?exam_id=' . $examId . '&message=' . urlencode($message));
                exit;
            } else {
                $error = "Failed to update question.";
            }
        }
    } else if (isset($_POST['delete_question']) && $questionId > 0) {
        // Delete question
        $result = $questionModel->delete($questionId);
        if ($result) {
            $message = "Question deleted successfully!";
            // Reload the page to display updated list
            header('Location: ' . BASE_URL . '/formateur/questions.php?exam_id=' . $examId . '&message=' . urlencode($message));
            exit;
        } else {
            $error = "Failed to delete question.";
        }
    }
}

// Get message from query string (for redirects)
if (empty($message) && isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get question data for edit form
$questionData = null;
if ($action === 'edit' && $questionId > 0) {
    // Get the specific question data
    $questions = $questionModel->getQuestionsByExam($examId);
    foreach ($questions as $q) {
        if ($q['id'] == $questionId) {
            $questionData = $q;
            break;
        }
    }
    
    if (!$questionData) {
        $error = "Question not found.";
        $action = 'list';
    }
}

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo SITE_NAME; ?></title>
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
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sign out
                </a>
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
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/formateur/questions.php">
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
                    <h1 class="h2">Manage Questions</h1>
                    <?php if ($examData): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo BASE_URL; ?>/formateur/questions.php?action=add&exam_id=<?php echo $examId; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add Question
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (!$examId): ?>
                    <!-- Exam selection screen -->
                    <div class="card">
                        <div class="card-header">
                            Select an Exam to Manage Questions
                        </div>
                        <div class="card-body">
                            <?php 
                            $exams = $examModel->getExamsByFormateurId($formateurId);
                            if (count($exams) > 0): 
                            ?>
                                <div class="list-group">
                                    <?php foreach ($exams as $exam): ?>
                                        <a href="<?php echo BASE_URL; ?>/formateur/questions.php?exam_id=<?php echo $exam['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($exam['name']); ?></h5>
                                                <small><?php echo date('Y-m-d', strtotime($exam['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($exam['description']); ?></p>
                                            <small>Time limit: <?php echo $exam['time_limit']; ?> minutes</small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>You haven't created any exams yet.</p>
                                <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create an Exam
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <!-- Add/Edit Question Form -->
                    <div class="card">
                        <div class="card-header">
                            <?php echo $action === 'add' ? 'Add New Question' : 'Edit Question'; ?> for <?php echo htmlspecialchars($examData['name']); ?>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo BASE_URL; ?>/formateur/questions.php?action=<?php echo $action; ?>&exam_id=<?php echo $examId; ?><?php echo $action === 'edit' ? '&question_id=' . $questionId : ''; ?>">
                                <div class="form-group">
                                    <label for="question_type">Question Type</label>
                                    <select class="form-control" id="question_type" name="question_type" required onchange="toggleQuestionType()">
                                        <option value="qcm" <?php echo ($action === 'edit' && $questionData['question_type'] === 'qcm') ? 'selected' : ''; ?>>Multiple Choice (QCM)</option>
                                        <option value="open" <?php echo ($action === 'edit' && $questionData['question_type'] === 'open') ? 'selected' : ''; ?>>Open Question</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="question_text">Question Text</label>
                                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo $action === 'edit' ? htmlspecialchars($questionData['question_text']) : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="points">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" min="1" required 
                                           value="<?php echo $action === 'edit' ? htmlspecialchars($questionData['points']) : '1'; ?>">
                                    <small class="form-text text-muted">
                                        Points to award for this question. For QCM questions, this will be automatically assigned based on the total points for the exam.
                                        <?php if ($action === 'add' && isset($_GET['question_type']) && $_GET['question_type'] === 'qcm'): ?>
                                            <?php 
                                            // Calculate default points for new QCM questions
                                            $defaultPoints = $questionModel->calculateDefaultPoints($examId);
                                            echo "Recommended points: " . $defaultPoints;
                                            ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div id="qcm_options" class="form-group">
                                    <label>Options (Multiple Choice)</label>
                                    <?php 
                                    $options = ($action === 'edit' && isset($questionData['options'])) ? $questionData['options'] : ['', '', '', ''];
                                    for ($i = 0; $i < 4; $i++): 
                                        $optionValue = isset($options[$i]) ? $options[$i] : '';
                                    ?>
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><?php echo chr(65 + $i); ?></span>
                                            </div>
                                            <input type="text" class="form-control" name="option[]" placeholder="Option <?php echo chr(65 + $i); ?>" value="<?php echo htmlspecialchars($optionValue); ?>">
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <div class="form-group" id="qcm_answer">
                                    <label for="correct_answer">Correct Answer(s) (for Multiple Choice)</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="correct_answer[]" value="A" id="correct_A" 
                                            <?php echo ($action === 'edit' && $questionData['question_type'] === 'qcm' && 
                                                (is_array($questionData['correct_answer']) ? 
                                                    in_array('A', $questionData['correct_answer']) : 
                                                    $questionData['correct_answer'] === 'A')) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="correct_A">A</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="correct_answer[]" value="B" id="correct_B"
                                            <?php echo ($action === 'edit' && $questionData['question_type'] === 'qcm' && 
                                                (is_array($questionData['correct_answer']) ? 
                                                    in_array('B', $questionData['correct_answer']) : 
                                                    $questionData['correct_answer'] === 'B')) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="correct_B">B</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="correct_answer[]" value="C" id="correct_C"
                                            <?php echo ($action === 'edit' && $questionData['question_type'] === 'qcm' && 
                                                (is_array($questionData['correct_answer']) ? 
                                                    in_array('C', $questionData['correct_answer']) : 
                                                    $questionData['correct_answer'] === 'C')) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="correct_C">C</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="correct_answer[]" value="D" id="correct_D"
                                            <?php echo ($action === 'edit' && $questionData['question_type'] === 'qcm' && 
                                                (is_array($questionData['correct_answer']) ? 
                                                    in_array('D', $questionData['correct_answer']) : 
                                                    $questionData['correct_answer'] === 'D')) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="correct_D">D</label>
                                    </div>
                                </div>
                                <div class="form-group" id="open_answer">
                                    <label for="correct_answer_open">Sample Answer (for Open Question)</label>
                                    <textarea class="form-control" id="correct_answer_open" name="correct_answer" rows="3"><?php echo ($action === 'edit' && $questionData['question_type'] === 'open') ? htmlspecialchars($questionData['correct_answer']) : ''; ?></textarea>
                                    <small class="form-text text-muted">This will be used as a reference for grading.</small>
                                </div>
                                <div class="form-group">
                                    <a href="<?php echo BASE_URL; ?>/formateur/questions.php?exam_id=<?php echo $examId; ?>" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" name="<?php echo $action === 'add' ? 'add_question' : 'update_question'; ?>" class="btn btn-primary">
                                        <?php echo $action === 'add' ? 'Add Question' : 'Update Question'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Question List Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4><?php echo htmlspecialchars($examData['name']); ?> - Questions</h4>
                                <a href="<?php echo BASE_URL; ?>/formateur/exams.php" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Exams
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (count($questions) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="bg-primary text-white">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="15%">Type</th>
                                                <th width="40%">Question</th>
                                                <th width="10%">Points</th>
                                                <th width="30%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($questions): ?>
                                                <?php foreach ($questions as $index => $question): ?>
                                                    <tr>
                                                        <td><?php echo $index + 1; ?></td>
                                                        <td>
                                                            <?php if ($question['question_type'] === 'qcm'): ?>
                                                                <span class="badge badge-pill badge-primary">QCM</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-pill badge-success">Open-ended</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                                                        <td><?php echo htmlspecialchars($question['points']); ?></td>
                                                        <td class="text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="<?php echo BASE_URL; ?>/formateur/questions.php?action=edit&exam_id=<?php echo $examId; ?>&question_id=<?php echo $question['id']; ?>" class="btn btn-primary">
                                                                    <i class="fas fa-edit"></i> Edit
                                                                </a>
                                                                <form method="post" action="<?php echo BASE_URL; ?>/formateur/questions.php?exam_id=<?php echo $examId; ?>&question_id=<?php echo $question['id']; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                                                    <button type="submit" name="delete_question" class="btn btn-danger">
                                                                        <i class="fas fa-trash"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No questions yet for this exam.</p>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>/formateur/questions.php?action=add&exam_id=<?php echo $examId; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Question
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Function to toggle question type fields
        function toggleQuestionType() {
            const questionType = document.getElementById('question_type').value;
            const qcmOptions = document.getElementById('qcm_options');
            const qcmAnswer = document.getElementById('qcm_answer');
            const openAnswer = document.getElementById('open_answer');
            
            if (questionType === 'qcm') {
                qcmOptions.style.display = 'block';
                qcmAnswer.style.display = 'block';
                openAnswer.style.display = 'none';
                document.getElementById('correct_answer_open').removeAttribute('required');
            } else {
                qcmOptions.style.display = 'none';
                qcmAnswer.style.display = 'none';
                openAnswer.style.display = 'block';
                document.getElementById('correct_answer_open').setAttribute('required', 'required');
            }
        }
        
        // Validation for QCM questions
        function validateForm(event) {
            const questionType = document.getElementById('question_type').value;
            
            if (questionType === 'qcm') {
                // Check if at least one correct answer is selected
                const checkboxes = document.querySelectorAll('input[name="correct_answer[]"]:checked');
                if (checkboxes.length === 0) {
                    event.preventDefault();
                    alert('Please select at least one correct answer for the multiple choice question.');
                    return false;
                }
                
                // Check if all options with selected correct answers have content
                let missingOption = false;
                checkboxes.forEach(function(checkbox) {
                    const optionIndex = ['A', 'B', 'C', 'D'].indexOf(checkbox.value);
                    if (optionIndex !== -1) {
                        const optionField = document.getElementsByName('option[]')[optionIndex];
                        if (!optionField.value.trim()) {
                            missingOption = true;
                        }
                    }
                });
                
                if (missingOption) {
                    event.preventDefault();
                    alert('All options marked as correct must have content.');
                    return false;
                }
            }
            
            return true;
        }
        
        // Call on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleQuestionType();
            
            // Add form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', validateForm);
            }
            
            // Dark mode toggle
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark-mode');
                });
            }
        });
    </script>
</body>
</html>
<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
