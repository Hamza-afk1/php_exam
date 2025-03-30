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
require_once __DIR__ . '/../models/Answer.php';

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
$answerModel = new Answer();

// Determine if we're editing a specific exam
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$questionId = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;

// Process form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_question']) || isset($_POST['update_question'])) {
        // Add or update question
        $questionData = [
            'exam_id' => (int)$_POST['exam_id'],
            'question_text' => $_POST['question_text'],
            'question_type' => $_POST['question_type'],
            'points' => isset($_POST['points']) ? (int)$_POST['points'] : 1
        ];
        
        // Handle correct answer based on question type
        if ($questionData['question_type'] === 'qcm') {
            // Multiple Choice - correct answers are checked options
            $correctAnswers = isset($_POST['is_correct']) ? $_POST['is_correct'] : [];
            $questionData['correct_answer'] = json_encode($correctAnswers);
        } else if ($questionData['question_type'] === 'true_false') {
            // True/False - correct answer is the checked option (0=True, 1=False)
            $correctAnswer = isset($_POST['is_correct']) && !empty($_POST['is_correct']) ? $_POST['is_correct'][0] : null;
            $questionData['correct_answer'] = $correctAnswer !== null ? ($correctAnswer == 0 ? 'True' : 'False') : '';
        } else {
            // Open Ended - correct answer is the model answer text
            $questionData['correct_answer'] = isset($_POST['answer_text'][0]) ? $_POST['answer_text'][0] : '';
        }
        
        // Validate exam_id belongs to this formateur
        $exam = $examModel->getById($questionData['exam_id']);
        if (!$exam || $exam['formateur_id'] != $formateurId) {
            $error = "You don't have permission to add questions to this exam.";
        } else {
            if (isset($_POST['add_question'])) {
                // Add new question
                $questionId = $questionModel->create($questionData);
                if ($questionId) {
                    // Add answers
                    $success = true;
                    for ($i = 0; $i < count($_POST['answer_text']); $i++) {
                        if (!empty(trim($_POST['answer_text'][$i]))) {
                            $answerData = [
                                'question_id' => $questionId,
                                'answer_text' => $_POST['answer_text'][$i],
                                'is_correct' => isset($_POST['is_correct']) && in_array($i, $_POST['is_correct']) ? 1 : 0,
                                'exam_id' => $questionData['exam_id'],
                                'stagiaire_id' => $formateurId
                            ];
                            if (!$answerModel->create($answerData)) {
                                $success = false;
                            }
                        }
                    }
                    
                    if ($success) {
                        $message = "Question added successfully!";
                        header('Location: ' . BASE_URL . '/formateur/questions_fixed.php?exam_id=' . $questionData['exam_id'] . '&message=' . urlencode($message));
                        exit;
                    } else {
                        $error = "Failed to add all answers!";
                    }
                } else {
                    $error = "Failed to add question!";
                }
            } else if (isset($_POST['update_question'])) {
                // Update existing question
                $questionId = (int)$_POST['question_id'];
                $question = $questionModel->getById($questionId);
                if (!$question || $question['exam_id'] != $questionData['exam_id']) {
                    $error = "Question not found or you don't have permission to edit it.";
                } else {
                    if ($questionModel->update($questionData, $questionId)) {
                        // Delete existing answers and add new ones
                        $answerModel->deleteByQuestionId($questionId);
                        
                        $success = true;
                        for ($i = 0; $i < count($_POST['answer_text']); $i++) {
                            if (!empty(trim($_POST['answer_text'][$i]))) {
                                $answerData = [
                                    'question_id' => $questionId,
                                    'answer_text' => $_POST['answer_text'][$i],
                                    'is_correct' => isset($_POST['is_correct']) && in_array($i, $_POST['is_correct']) ? 1 : 0,
                                    'exam_id' => $questionData['exam_id'],
                                    'stagiaire_id' => $formateurId
                                ];
                                if (!$answerModel->create($answerData)) {
                                    $success = false;
                                }
                            }
                        }
                        
                        if ($success) {
                            $message = "Question updated successfully!";
                            header('Location: ' . BASE_URL . '/formateur/questions_fixed.php?exam_id=' . $questionData['exam_id'] . '&message=' . urlencode($message));
                            exit;
                        } else {
                            $error = "Failed to update all answers!";
                        }
                    } else {
                        $error = "Failed to update question!";
                    }
                }
            }
        }
    } else if (isset($_POST['delete_question'])) {
        // Delete question
        $questionId = (int)$_POST['question_id'];
        $examId = (int)$_POST['exam_id'];
        $question = $questionModel->getById($questionId);
        
        // Check if the question exists and belongs to an exam owned by this formateur
        if ($question) {
            $exam = $examModel->getById($question['exam_id']);
            if ($exam && $exam['formateur_id'] == $formateurId) {
                // Delete the question and its answers
                if ($questionModel->delete($questionId)) {
                    $message = "Question deleted successfully!";
                    header('Location: ' . BASE_URL . '/formateur/questions_fixed.php?exam_id=' . $examId . '&message=' . urlencode($message));
                    exit;
                } else {
                    $error = "Failed to delete question!";
                }
            } else {
                $error = "You don't have permission to delete this question.";
            }
        } else {
            $error = "Question not found!";
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

<!-- Main Content Structure -->
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-question-circle mr-2"></i> Question Management
    </h1>
    <p class="page-subtitle">Create and manage questions for your exams</p>
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

<!-- Action Buttons -->
<div class="action-buttons mb-4">
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addQuestionModal" <?php echo $examId ? '' : 'disabled'; ?>>
        <i class="fas fa-plus-circle mr-1"></i> Add New Question
    </button>
    <a href="<?php echo BASE_URL; ?>/formateur/exams.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back to Exams
    </a>
</div>

<!-- Exams List for Selection -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-clipboard-list mr-2"></i> Select an Exam</h5>
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
                <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=add" class="btn btn-primary">
                    Create Your First Exam
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($exams as $exam): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card exam-card <?php echo $examId == $exam['id'] ? 'border-primary' : ''; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($exam['name']); ?></h5>
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars(substr($exam['description'], 0, 100)) . (strlen($exam['description']) > 100 ? '...' : ''); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-info">
                                        <?php 
                                        // Count questions for this exam
                                        $questionCount = count($questionModel->getQuestionsByExamId($exam['id']));
                                        echo $questionCount . ' question' . ($questionCount !== 1 ? 's' : '');
                                        ?>
                                    </span>
                                    <a href="?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm <?php echo $examId == $exam['id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        <?php echo $examId == $exam['id'] ? 'Selected' : 'Select'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($examId): ?>
    <?php
    // Get the selected exam
    $selectedExam = $examModel->getById($examId);
    if ($selectedExam && $selectedExam['formateur_id'] == $formateurId):
        // Get questions for this exam
        $questions = $questionModel->getQuestionsByExamId($examId);
    ?>
        <!-- Questions List -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list mr-2"></i> Questions for "<?php echo htmlspecialchars($selectedExam['name']); ?>"
                </h5>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addQuestionModal">
                    <i class="fas fa-plus-circle mr-1"></i> Add Question
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($questions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-question-circle empty-state-icon"></i>
                        <p class="empty-state-text">No questions added to this exam yet.</p>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addQuestionModal">
                            <i class="fas fa-plus-circle mr-1"></i> Add Your First Question
                        </button>
                    </div>
                <?php else: ?>
                    <div class="questions-list">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="question-card mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <span class="badge badge-secondary mr-2">Q<?php echo $index + 1; ?></span>
                                            <?php echo htmlspecialchars($question['question_text']); ?>
                                        </h6>
                                        <div>
                                            <span class="badge badge-info mr-2"><?php echo $question['question_type']; ?></span>
                                            <span class="badge badge-primary mr-2"><?php echo $question['points'] ?? 1; ?> pts</span>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-question-btn" 
                                                        data-question-id="<?php echo $question['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-question-btn" 
                                                        data-toggle="modal" 
                                                        data-target="#deleteQuestionModal" 
                                                        data-question-id="<?php echo $question['id']; ?>"
                                                        data-exam-id="<?php echo $examId; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // Get answers for this question
                                        $answers = $answerModel->getAnswersByQuestionId($question['id']);
                                        if (!empty($answers)):
                                        ?>
                                            <div class="answers-list">
                                                <h6 class="text-muted mb-3">Answers:</h6>
                                                <div class="row">
                                                    <?php foreach ($answers as $answer): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="answer-item d-flex align-items-center">
                                                                <i class="fas <?php echo $answer['is_correct'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> mr-2"></i>
                                                                <span><?php echo htmlspecialchars($answer['answer_text']); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No answers added to this question.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i> The selected exam does not exist or you don't have permission to view it.
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Add/Edit Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" role="dialog" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addQuestionModalLabel">
                    <i class="fas fa-plus-circle mr-2"></i> Add New Question
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="questionForm" method="post" action="questions_fixed.php">
                    <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                    <input type="hidden" name="question_id" id="question_id" value="">
                    
                    <div class="form-group">
                        <label for="question_text">Question Text</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="question_type">Question Type</label>
                            <select class="form-control" id="question_type" name="question_type" required>
                                <option value="qcm">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="open">Open Ended</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="points">Points</label>
                            <input type="number" class="form-control" id="points" name="points" value="1" min="1" required>
                        </div>
                    </div>
                    
                    <div id="answers-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Answers</h5>
                            <button type="button" id="add-answer-btn" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Add Answer
                            </button>
                        </div>
                        
                        <div class="answer-row mb-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="is_correct[]" value="0">
                                    </div>
                                </div>
                                <input type="text" class="form-control answer-input" name="answer_text[]" placeholder="Answer text" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-danger remove-answer-btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="answer-row mb-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="is_correct[]" value="1">
                                    </div>
                                </div>
                                <input type="text" class="form-control answer-input" name="answer_text[]" placeholder="Answer text" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-danger remove-answer-btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3" id="question-type-info">
                        <i class="fas fa-info-circle mr-2"></i> For multiple choice questions, check one or more correct answers.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" form="questionForm" name="add_question" class="btn btn-primary" id="save-question-btn">
                    <i class="fas fa-plus-circle mr-1"></i> Add Question
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Question Modal -->
<div class="modal fade" id="deleteQuestionModal" tabindex="-1" role="dialog" aria-labelledby="deleteQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteQuestionModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this question?</p>
                <p class="text-danger">This action cannot be undone. All answers associated with this question will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="post" action="questions_fixed.php" id="deleteQuestionForm">
                    <input type="hidden" name="question_id" id="questionIdToDelete" value="">
                    <input type="hidden" name="exam_id" id="examIdForDelete" value="<?php echo $examId; ?>">
                    <button type="submit" name="delete_question" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript for form handling
    document.addEventListener('DOMContentLoaded', function() {
        // Force enable form fields in the modal when it opens
        $('#addQuestionModal').on('shown.bs.modal', function () {
            console.log('Modal opened - ensuring form fields are enabled');
            // Enable all form inputs within the modal
            const modal = document.getElementById('addQuestionModal');
            const inputs = modal.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.disabled = false;
                input.readOnly = false;
            });
            
            // Specifically target the question text area
            const questionText = document.getElementById('question_text');
            if (questionText) {
                questionText.disabled = false;
                questionText.readOnly = false;
                setTimeout(() => {
                    questionText.focus();
                }, 200);
            }
            
            // Enable answer inputs
            const answerInputs = modal.querySelectorAll('.answer-input');
            answerInputs.forEach(input => {
                input.disabled = false;
                input.readOnly = false;
            });
            
            console.log('All form fields enabled');
        });
        
        // Function to create a new answer row
        function createAnswerRow(index, value) {
            const div = document.createElement('div');
            div.className = 'answer-row mb-3';
            div.innerHTML = `
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text">
                            <input type="checkbox" name="is_correct[]" value="${index}">
                        </div>
                    </div>
                    <input type="text" class="form-control answer-input" name="answer_text[]" placeholder="Answer text" value="${value || ''}" required>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-danger remove-answer-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            return div;
        }

        // Question type change handler
        const questionTypeSelect = document.getElementById('question_type');
        if (questionTypeSelect) {
            questionTypeSelect.addEventListener('change', function() {
                const questionType = this.value;
                const answersContainer = document.getElementById('answers-container');
                const addAnswerBtn = document.getElementById('add-answer-btn');
                const questionTypeInfo = document.getElementById('question-type-info');
                
                // Clear existing answers
                while (answersContainer.querySelector('.answer-row')) {
                    answersContainer.querySelector('.answer-row').remove();
                }
                
                if (questionType === 'true_false') {
                    // Add True and False options
                    answersContainer.appendChild(createAnswerRow(0, 'True'));
                    answersContainer.appendChild(createAnswerRow(1, 'False'));
                    addAnswerBtn.style.display = 'none';
                    questionTypeInfo.innerHTML = '<i class="fas fa-info-circle mr-2"></i> Select the correct answer (True or False).';
                } else if (questionType === 'open') {
                    // Add one empty answer for model answer
                    answersContainer.appendChild(createAnswerRow(0, ''));
                    addAnswerBtn.style.display = 'none';
                    questionTypeInfo.innerHTML = '<i class="fas fa-info-circle mr-2"></i> Enter a model answer for reference.';
                } else {
                    // Multiple choice - add default answers
                    answersContainer.appendChild(createAnswerRow(0, ''));
                    answersContainer.appendChild(createAnswerRow(1, ''));
                    addAnswerBtn.style.display = 'block';
                    questionTypeInfo.innerHTML = '<i class="fas fa-info-circle mr-2"></i> Check one or more correct answers.';
                }
                
                // Ensure inputs are enabled in the newly created rows
                setTimeout(function() {
                    const inputs = document.querySelectorAll('.answer-input');
                    inputs.forEach(input => {
                        input.disabled = false;
                        input.readOnly = false;
                    });
                }, 50);
            });
        }
        
        // Add answer button handler
        const addAnswerBtn = document.getElementById('add-answer-btn');
        if (addAnswerBtn) {
            addAnswerBtn.addEventListener('click', function() {
                const answersContainer = document.getElementById('answers-container');
                const answerRows = answersContainer.querySelectorAll('.answer-row');
                const newIndex = answerRows.length;
                const newRow = createAnswerRow(newIndex, '');
                answersContainer.appendChild(newRow);
                
                // Make sure the new input is enabled
                const newInput = newRow.querySelector('.answer-input');
                if (newInput) {
                    newInput.disabled = false;
                    newInput.readOnly = false;
                    setTimeout(() => {
                        newInput.focus();
                    }, 100);
                }
            });
        }
        
        // Remove answer button handler using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-answer-btn')) {
                const answersContainer = document.getElementById('answers-container');
                const answerRows = answersContainer.querySelectorAll('.answer-row');
                
                if (answerRows.length > 2 && document.getElementById('question_type').value === 'qcm') {
                    const rowToRemove = e.target.closest('.answer-row');
                    rowToRemove.remove();
                    
                    // Update indexes
                    const updatedRows = answersContainer.querySelectorAll('.answer-row');
                    updatedRows.forEach((row, idx) => {
                        row.querySelector('input[type="checkbox"]').value = idx;
                    });
                } else if (document.getElementById('question_type').value === 'qcm') {
                    alert('Multiple choice questions must have at least 2 answers.');
                }
            }
        });

        // Edit question button handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-question-btn')) {
                const questionId = e.target.closest('.edit-question-btn').dataset.questionId;
                
                // Fetch question data
                fetch('<?php echo BASE_URL; ?>/formateur/update_questions.php?question_id=' + questionId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const question = data.question;
                            const answers = data.answers;
                            
                            // Update form fields
                            document.getElementById('question_id').value = question.id;
                            document.getElementById('question_text').value = question.question_text;
                            document.getElementById('question_type').value = question.question_type;
                            document.getElementById('points').value = question.points || 1;
                            
                            // Update modal title and save button
                            document.getElementById('addQuestionModalLabel').innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Question';
                            document.getElementById('save-question-btn').innerHTML = '<i class="fas fa-save mr-1"></i> Save Changes';
                            document.getElementById('save-question-btn').name = 'update_question';
                            
                            // Clear existing answers
                            const answersContainer = document.getElementById('answers-container');
                            while (answersContainer.querySelector('.answer-row')) {
                                answersContainer.querySelector('.answer-row').remove();
                            }
                            
                            // Add answers
                            if (answers && answers.length > 0) {
                                answers.forEach((answer, index) => {
                                    const row = createAnswerRow(index, answer.answer_text);
                                    answersContainer.appendChild(row);
                                    
                                    // Check correct answers
                                    if (answer.is_correct == 1) {
                                        row.querySelector('input[type="checkbox"]').checked = true;
                                    }
                                });
                            } else {
                                // Add default empty answers
                                answersContainer.appendChild(createAnswerRow(0, ''));
                                answersContainer.appendChild(createAnswerRow(1, ''));
                            }
                            
                            // Trigger question type change to update UI
                            const event = new Event('change');
                            document.getElementById('question_type').dispatchEvent(event);
                            
                            // Show modal
                            $('#addQuestionModal').modal('show');
                        } else {
                            alert('Error loading question: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to load question data.');
                    });
            }
        });
        
        // Delete question modal handler
        $('#deleteQuestionModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const questionId = button.data('question-id');
            const examId = button.data('exam-id');
            
            document.getElementById('questionIdToDelete').value = questionId;
            document.getElementById('examIdForDelete').value = examId;
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?> 