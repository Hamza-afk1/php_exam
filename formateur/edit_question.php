<?php
// Edit Question Form
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

// Get parameters
$questionId = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Check if question exists
$question = $questionModel->getById($questionId);
if (!$question) {
    header('Location: questions.php?exam_id=' . $examId . '&error=' . urlencode('Question not found'));
    exit;
}

// Get answers
$answers = $answerModel->getAnswersByQuestionId($questionId);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_question'])) {
    // Handle question update logic
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
        
    // Update question
        if ($questionModel->update($questionData, $questionId)) {
        // Delete existing answers
            $answerModel->deleteByQuestionId($questionId);
            
        // Add new answers
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
            
        $message = $success ? 'Question updated successfully' : 'Question updated but some answers were not saved';
        header('Location: questions.php?exam_id=' . $examId . '&message=' . urlencode($message));
                exit;
            } else {
        $error = 'Failed to update question';
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
                    <h5 class="mb-0"><i class="fas fa-edit mr-2"></i> Edit Question</h5>
    </div>
    <div class="card-body">
                    <form method="post" action="edit_question.php?question_id=<?php echo $questionId; ?>&exam_id=<?php echo $examId; ?>">
                        <!-- Question Fields -->
            <div class="form-group">
                <label for="question_text">Question Text</label>
                            <textarea class="form-control" id="question_text" name="question_text" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="question_type">Question Type</label>
                                <select class="form-control" id="question_type" name="question_type">
                        <option value="qcm" <?php echo $question['question_type'] === 'qcm' ? 'selected' : ''; ?>>Multiple Choice</option>
                        <option value="open" <?php echo $question['question_type'] === 'open' ? 'selected' : ''; ?>>Open Ended</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="points">Points</label>
                                <input type="number" class="form-control" id="points" name="points" value="<?php echo $question['points']; ?>" min="1">
                            </div>
                        </div>
                        
                        <!-- Answers Section -->
                        <h5 class="mt-4">Answers</h5>
                        <div id="answers-container">
                            <?php if (empty($answers)): ?>
                                <!-- Default empty answer -->
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
                            <?php else: ?>
                                <?php foreach ($answers as $index => $answer): ?>
                                    <div class="form-group answer-row">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                                    <input type="checkbox" name="is_correct[]" value="<?php echo $index; ?>" <?php echo $answer['is_correct'] ? 'checked' : ''; ?>>
                            </div>
                        </div>
                                            <input type="text" class="form-control" name="answer_text[]" placeholder="Answer text" value="<?php echo htmlspecialchars($answer['answer_text']); ?>">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-danger remove-answer-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
                        <button type="button" id="add-answer-btn" class="btn btn-sm btn-outline-primary mb-4">
                            <i class="fas fa-plus"></i> Add Answer
                        </button>
                        
                        <!-- Submit Buttons -->
            <div class="form-group mt-4">
                            <a href="questions.php?exam_id=<?php echo $examId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_question" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
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

    // Add answer button click handler
    $(document).on('click', '#add-answer-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const answersCount = $('#answers-container .answer-row').length;
        $('#answers-container').append(addAnswerRow(answersCount));
    });

    // Remove answer button click handler
    $(document).on('click', '.remove-answer-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const answersCount = $('#answers-container .answer-row').length;
        const questionType = $('#question_type').val();

        if (questionType === 'qcm') {
            if (answersCount > 2) {
                $(this).closest('.answer-row').remove();
                // Renumber remaining answers
                $('#answers-container .answer-row').each(function(index) {
                    $(this).find('input[type="checkbox"]').val(index);
                });
            } else {
                alert('Multiple choice questions must have at least 2 answers.');
            }
        } else {
            if (answersCount > 1) {
                $(this).closest('.answer-row').remove();
                // Renumber remaining answers
                $('#answers-container .answer-row').each(function(index) {
                    $(this).find('input[type="checkbox"]').val(index);
                });
            } else {
                alert('You must have at least one answer.');
            }
        }
    });

    // Question type change handler
    $('#question_type').change(function() {
        const questionType = $(this).val();
        if (questionType === 'open') {
            $('#add-answer-btn').hide();
            // Keep only first answer for open-ended questions
            if ($('#answers-container .answer-row').length > 1) {
                $('#answers-container .answer-row:not(:first)').remove();
            }
        } else {
            $('#add-answer-btn').show();
            // Ensure at least 2 answers for multiple choice
            if ($('#answers-container .answer-row').length < 2) {
                const currentCount = $('#answers-container .answer-row').length;
                $('#answers-container').append(addAnswerRow(currentCount));
            }
        }
    });

    // Initialize on page load
    $('#question_type').trigger('change');
});
</script>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?> 