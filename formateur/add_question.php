<?php
// Add Question Form
// Simple direct form approach without using modals or AJAX

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

// Get exam ID parameter
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Validate the exam ID
$exam = $examModel->getById($examId);
if (!$exam) {
    header('Location: questions.php?error=' . urlencode('Invalid exam ID'));
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    // Add new question
    $questionData = [
        'exam_id' => $examId,
        'question_text' => $_POST['question_text'],
        'question_type' => $_POST['question_type'],
        'points' => (int)$_POST['points']
    ];
    
    // Handle correct answer based on question type
    if ($questionData['question_type'] === 'qcm') {
        $correctAnswers = isset($_POST['is_correct']) ? $_POST['is_correct'] : [];
        $questionData['correct_answer'] = json_encode($correctAnswers);
    } else {
        $questionData['correct_answer'] = isset($_POST['answer_text'][0]) ? $_POST['answer_text'][0] : '';
    }
    
    // Create question
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
                    'exam_id' => $examId,
                    'stagiaire_id' => $formateurId
                ];
                
                if (!$answerModel->create($answerData)) {
                    $success = false;
                }
            }
        }
        
        $message = $success ? 'Question added successfully' : 'Question added but some answers were not saved';
        header('Location: questions.php?exam_id=' . $examId . '&message=' . urlencode($message));
        exit;
    } else {
        $error = 'Failed to add question';
    }
}

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle mr-2"></i> Add New Question</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="add_question.php?exam_id=<?php echo $examId; ?>">
                        <!-- Question Fields -->
                        <div class="form-group">
                            <label for="question_text">Question Text</label>
                            <textarea class="form-control" id="question_text" name="question_text" required></textarea>
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
                        
                        <!-- Answers Section -->
                        <h5 class="mt-4">Answers</h5>
                        <div id="answers-container">
                            <!-- Default empty answers -->
                            <div class="form-group answer-row">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <input type="checkbox" name="is_correct[]" value="0">
                                        </div>
                                    </div>
                                    <input type="text" class="form-control" name="answer_text[]" placeholder="Answer text">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-danger remove-answer-btn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group answer-row">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <input type="checkbox" name="is_correct[]" value="1">
                                        </div>
                                    </div>
                                    <input type="text" class="form-control" name="answer_text[]" placeholder="Answer text">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-danger remove-answer-btn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="add-answer-btn" class="btn btn-sm btn-outline-primary mb-4">
                            <i class="fas fa-plus"></i> Add Answer
                        </button>
                        
                        <!-- Submit Buttons -->
                        <div class="form-group mt-4">
                            <a href="questions.php?exam_id=<?php echo $examId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add new answer
    $('#add-answer-btn').click(function() {
        const answersCount = $('.answer-row').length;
        const newRow = `
            <div class="form-group answer-row">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text">
                            <input type="checkbox" name="is_correct[]" value="${answersCount}">
                        </div>
                    </div>
                    <input type="text" class="form-control" name="answer_text[]" placeholder="Answer text">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-danger remove-answer-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#answers-container').append(newRow);
    });
    
    // Remove answer
    $(document).on('click', '.remove-answer-btn', function() {
        const answersCount = $('.answer-row').length;
        if (answersCount > 2 && $('#question_type').val() === 'qcm') {
            $(this).closest('.answer-row').remove();
            
            // Renumber the remaining answers
            $('.answer-row').each(function(index) {
                $(this).find('input[type="checkbox"]').val(index);
            });
        } else if ($('#question_type').val() === 'qcm') {
            alert('Multiple choice questions must have at least 2 answers.');
        } else if (answersCount > 1) {
            $(this).closest('.answer-row').remove();
        } else {
            alert('You must have at least one answer');
        }
    });
    
    // Toggle add answer button based on question type
    $('#question_type').change(function() {
        if ($(this).val() === 'open') {
            $('#add-answer-btn').hide();
            // Remove extra answers for open-ended questions
            if ($('.answer-row').length > 1) {
                $('.answer-row:not(:first)').remove();
            }
        } else {
            $('#add-answer-btn').show();
            // Make sure we have at least 2 answers for multiple choice
            if ($('.answer-row').length < 2) {
                const answersCount = $('.answer-row').length;
                $('#add-answer-btn').click();
            }
        }
    }).trigger('change');
});
</script>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?> 