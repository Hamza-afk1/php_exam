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
                    header('Location: ' . BASE_URL . '/formateur/questions.php?exam_id=' . $examId . '&message=' . urlencode($message));
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
    <a href="add_question.php?exam_id=<?php echo $examId; ?>" class="btn btn-primary" <?php echo $examId ? '' : 'disabled'; ?>>
        <i class="fas fa-plus-circle mr-1"></i> Add New Question
    </a>
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
                <a href="add_question.php?exam_id=<?php echo $examId; ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle mr-1"></i> Add Question
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($questions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-question-circle empty-state-icon"></i>
                        <p class="empty-state-text">No questions added to this exam yet.</p>
                        <a href="add_question.php?exam_id=<?php echo $examId; ?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle mr-1"></i> Add Your First Question
                        </a>
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
                                                <a href="edit_question.php?question_id=<?php echo $question['id']; ?>&exam_id=<?php echo $examId; ?>" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="delete_question.php?question_id=<?php echo $question['id']; ?>&exam_id=<?php echo $examId; ?>&delete_question=1" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this question? This action cannot be undone. All answers associated with this question will also be deleted.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" role="dialog" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                <form id="questionForm" method="post">
                    <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                    <input type="hidden" name="question_id" id="question_id" value="">
                    
                    <div class="form-group">
                        <label for="question_text">Question Text</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="question_type">Question Type</label>
                            <select class="form-control" id="question_type" name="question_type">
                                <option value="qcm">Multiple Choice</option>
                                <option value="open">Open Ended</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="points">Points</label>
                            <input type="number" class="form-control" id="points" name="points" value="1" min="1">
                        </div>
                    </div>
                    
                    <div id="answers-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Answers</h5>
                            <button type="button" id="add-answer-btn" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Add Answer
                            </button>
                        </div>
                        
                        <div id="answer-rows">
                            <!-- Answer rows will be added here dynamically -->
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3" id="question-type-info">
                        <i class="fas fa-info-circle mr-2"></i> For multiple choice questions, check one or more correct answers.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" form="questionForm" class="btn btn-primary" id="save-question-btn" name="add_question">
                    <i class="fas fa-plus-circle mr-1"></i> Add Question
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Function to add a new answer row
        function addAnswerRow(index, value = '', isChecked = false) {
            return `
                <div class="form-group answer-row">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <input type="checkbox" name="is_correct[]" value="${index}" ${isChecked ? 'checked' : ''}>
                            </div>
                        </div>
                        <input type="text" class="form-control" name="answer_text[]" placeholder="Answer text" value="${value}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-danger remove-answer-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        // Initialize answers
        function initializeAnswers() {
            $('#answer-rows').empty();
            const questionType = $('#question_type').val();
            
            if (questionType === 'qcm') {
                $('#answer-rows').append(addAnswerRow(0));
                $('#answer-rows').append(addAnswerRow(1));
                $('#add-answer-btn').show();
                $('#question-type-info').html('<i class="fas fa-info-circle mr-2"></i> Check one or more correct answers.');
            } else {
                $('#answer-rows').append(addAnswerRow(0));
                $('#add-answer-btn').hide();
                $('#question-type-info').html('<i class="fas fa-info-circle mr-2"></i> Enter a model answer for reference.');
            }
        }

        // Add answer button click handler
        $(document).on('click', '#add-answer-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const answerCount = $('#answer-rows .answer-row').length;
            $('#answer-rows').append(addAnswerRow(answerCount));
        });

        // Remove answer button click handler
        $(document).on('click', '.remove-answer-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const answerRows = $('#answer-rows .answer-row');
            const questionType = $('#question_type').val();

            if (questionType === 'qcm') {
                if (answerRows.length > 2) {
                    $(this).closest('.answer-row').remove();
                    // Renumber remaining answers
                    $('#answer-rows .answer-row').each(function(index) {
                        $(this).find('input[type="checkbox"]').val(index);
                    });
                } else {
                    alert('Multiple choice questions must have at least 2 answers.');
                }
            } else {
                if (answerRows.length > 1) {
                    $(this).closest('.answer-row').remove();
                    // Renumber remaining answers
                    $('#answer-rows .answer-row').each(function(index) {
                        $(this).find('input[type="checkbox"]').val(index);
                    });
                } else {
                    alert('You must have at least one answer.');
                }
            }
        });

        // Question type change handler
        $('#question_type').change(function() {
            initializeAnswers();
        });

        // Initialize on page load
        initializeAnswers();

        // Reset form when modal is opened
        $('#addQuestionModal').on('show.bs.modal', function() {
            $('#questionForm')[0].reset();
            $('#question_id').val('');
            initializeAnswers();
        });

        // When Edit button is clicked (using event delegation)
        $(document).on('click', '.edit-question-btn', function(e) {
            e.preventDefault();
            const questionId = $(this).data('question-id');
            console.log('Edit button clicked for question ID:', questionId);
            
            // Reset form
                $('#questionForm')[0].reset();
            $('#question_id').val(questionId);
            
            // Set modal title and button
            $('#addQuestionModalLabel').html('<i class="fas fa-edit mr-2"></i> Edit Question');
            $('#save-question-btn').html('<i class="fas fa-save mr-1"></i> Save Changes').attr('name', 'update_question');
            
            // Force the modal to be shown
            setTimeout(function() {
                $('#addQuestionModal').modal('show');
                console.log('Modal should be visible now (with timeout)');
                console.log('BASE_URL value:', '<?php echo BASE_URL; ?>');
                
                // Fetch question data using an absolute path
                $.ajax({
                    url: 'get_question_json.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { id: questionId },
                    beforeSend: function() {
                        console.log('Sending AJAX request to:', 'get_question_json.php?id=' + questionId);
                    },
                    success: function(data) {
                        console.log('Received data:', data);
                        if (data.success) {
                            const question = data.question;
                            const answers = data.answers;
                            
                            // Fill form fields
                            $('#question_text').val(question.question_text);
                            $('#question_type').val(question.question_type);
                            $('#points').val(question.points);
                            
                            // Clear existing answers
                            $('#answer-rows').empty();
                            
                            // Add answers
                            if (answers && answers.length > 0) {
                                $.each(answers, function(index, answer) {
                                    const row = addAnswerRow(index, answer.answer_text);
                                    if (answer.is_correct == 1) {
                                        row.find('input[type="checkbox"]').prop('checked', true);
                                    }
                                });
                            } else {
                                initializeAnswers();
                            }
                            
                            // Update UI based on question type
                            if (question.question_type === 'open') {
                                $('#add-answer-btn').hide();
                                $('#question-type-info').html('<i class="fas fa-info-circle mr-2"></i> Enter a model answer for reference.');
                            } else {
                                $('#add-answer-btn').show();
                                $('#question-type-info').html('<i class="fas fa-info-circle mr-2"></i> Check one or more correct answers.');
                            }
                        } else {
                            alert('Failed to load question: ' + data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.log('Response Text:', xhr.responseText);
                        alert('Error loading question data: ' + error);
                    }
                });
            }, 100);
        });
        
        // Ensure modal is properly initialized
        $('#addQuestionModal').on('shown.bs.modal', function() {
            $('#question_text').focus();
        });
        
        // Handle delete question form submission
        $('.delete-question-form').on('submit', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this question? This action cannot be undone. All answers associated with this question will also be deleted.')) {
                this.submit();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?>