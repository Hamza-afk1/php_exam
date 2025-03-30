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
require_once __DIR__ . '/../models/Result.php';
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
$resultModel = new Result();
$questionModel = new Question();
$answerModel = new Answer();

// Get result ID from URL
$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the result and related data
$error = '';
try {
    $db = new Database();
    
    // Get result details with exam and student info
    $query = "SELECT r.*, e.name as exam_name, e.description as exam_description, 
                     e.time_limit, COALESCE(e.passing_score, 70) as passing_score,
                     u.username as stagiaire_username, u.full_name as stagiaire_name,
                     u.email as stagiaire_email
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              JOIN users u ON r.stagiaire_id = u.id
              WHERE r.id = :result_id AND e.formateur_id = :formateur_id";
    
    $stmt = $db->prepare($query);
    $db->execute($stmt, [
        ':result_id' => $resultId,
        ':formateur_id' => $formateurId
    ]);
    
    $result = $db->single($stmt);
    
    if (!$result) {
        $error = 'Result not found or you do not have permission to view it.';
    } else {
        // Get the answers given by the student
        $answerQuery = "SELECT a.*, q.question_text, q.question_type, 
                               ca.answer_text as correct_answer_text,
                               ca.id as correct_answer_id
                        FROM answers a
                        JOIN questions q ON a.question_id = q.id
                        LEFT JOIN answers ca ON ca.question_id = q.id AND ca.is_correct = 1
                        WHERE a.result_id = :result_id
                        ORDER BY q.id";
        
        $stmt = $db->prepare($answerQuery);
        $db->execute($stmt, [':result_id' => $resultId]);
        $studentAnswers = $db->resultSet($stmt);
    }
} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
}

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-check mr-2"></i> Exam Result Details
                </h1>
                <p class="page-subtitle">Viewing detailed results for a student's exam attempt</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
    <div class="text-center mt-4">
        <a href="<?php echo BASE_URL; ?>/formateur/results.php" class="btn btn-primary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Results
        </a>
    </div>
<?php elseif (!$result): ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i> Result not found or you don't have permission to view it.
    </div>
    <div class="text-center mt-4">
                    <a href="<?php echo BASE_URL; ?>/formateur/results.php" class="btn btn-primary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Results
                    </a>
                </div>
            <?php else: ?>
                <!-- Result Summary Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle mr-2"></i> Result Summary
                        </h5>
                        <a href="<?php echo BASE_URL; ?>/formateur/results.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Results
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-muted">Exam Information</h5>
                                <p><strong>Exam Name:</strong> <?php echo htmlspecialchars($result['exam_name']); ?></p>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($result['exam_description']); ?></p>
                                <p><strong>Time Limit:</strong> <?php echo (int)$result['time_limit']; ?> minutes</p>
                                <p><strong>Passing Score:</strong> <?php echo (int)$result['passing_score']; ?>%</p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-muted">Student Information</h5>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($result['stagiaire_name'] ?: $result['stagiaire_username']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($result['stagiaire_email']); ?></p>
                                <p><strong>Date Taken:</strong> <?php echo date('F d, Y h:i A', strtotime($result['created_at'])); ?></p>
                                <p><strong>Time Spent:</strong> <?php echo (int)$result['time_spent']; ?> minutes</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <h3>Final Score: 
                                    <span class="badge <?php echo $result['score'] >= $result['passing_score'] ? 'badge-success' : 'badge-danger'; ?> p-2">
                                        <?php echo (int)$result['score']; ?>%
                                    </span>
                                </h3>
                                <p class="mt-2">
                                    <?php if ($result['score'] >= $result['passing_score']): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle mr-1"></i> Passed
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger">
                                            <i class="fas fa-times-circle mr-1"></i> Failed
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Student Answers -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list-ul mr-2"></i> Student Responses</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($studentAnswers)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> No answers found for this result.
                            </div>
                        <?php else: ?>
                            <?php 
                            $currentQuestionId = 0;
                            $questionNumber = 1;
                            ?>
                            <div class="student-answers">
                                <?php foreach ($studentAnswers as $index => $answer): ?>
                                    <?php 
                                    // Start a new question card if this is a new question
                                    if ($currentQuestionId != $answer['question_id']):
                                        // Close the previous card if not the first one
                                        if ($currentQuestionId != 0):
                                            echo '</div></div></div>';
                                        endif;
                                        
                                        $currentQuestionId = $answer['question_id'];
                                    ?>
                                        <div class="question-card mb-4">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">
                                                        <span class="badge badge-secondary mr-2">Q<?php echo $questionNumber++; ?></span>
                                                        <?php echo htmlspecialchars($answer['question_text']); ?>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                    <?php endif; ?>
                                    
                                    <div class="answer-item mb-2">
                                        <?php if ($answer['question_type'] == 'multiple_choice' || $answer['question_type'] == 'true_false'): ?>
                                            <div class="d-flex align-items-center">
                                                <span class="mr-3">Student's Answer:</span>
                                                <?php if ($answer['is_correct'] == 1): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check mr-1"></i> <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times mr-1"></i> <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                    </span>
                                                    <?php if (!empty($answer['correct_answer_text'])): ?>
                                                        <span class="ml-3">Correct Answer: 
                                                            <span class="badge badge-success"><?php echo htmlspecialchars($answer['correct_answer_text']); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($answer['question_type'] == 'open'): ?>
                                            <!-- Open-ended Question -->
                                            <div class="card mb-3">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <span>Open-ended Question</span>
                                                    <?php if (isset($answer['graded_points'])): ?>
                                                    <span class="badge badge-info">
                                                        Score: <?php echo $answer['graded_points']; ?> / <?php echo $answer['points']; ?> points
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="badge badge-warning">Not graded yet</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body">
                                                    <p><strong>Student's Answer:</strong></p>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif ($answer['question_type'] == 'open_ended'): ?>
                                            <div>
                                                <p><strong>Student's Response:</strong></p>
                                                <div class="p-3 bg-light rounded">
                                                    <?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?>
                                                </div>
                                                
                                                <?php if (!empty($answer['correct_answer_text'])): ?>
                                                    <p class="mt-3"><strong>Model Answer:</strong></p>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($answer['correct_answer_text'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($answer['graded_points'])): ?>
                                                    <div class="mt-3">
                                                        <p><strong>Points Awarded:</strong> 
                                                            <span class="badge badge-primary">
                                                                <?php echo $answer['graded_points']; ?> / <?php echo $answer['max_points']; ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php 
                                    // Close the last question card
                                    if ($index == count($studentAnswers) - 1):
                                        echo '</div></div></div>';
                                    endif;
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Export/Print Options -->
                <div class="text-center mb-4">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i> Print Result
                    </button>
                    <a href="results.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Results
                    </a>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Result Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Student:</strong> <?php echo htmlspecialchars($result['stagiaire_username']); ?></p>
                                <p><strong>Exam:</strong> <?php echo htmlspecialchars($result['exam_name']); ?></p>
                                <p><strong>Date Taken:</strong> <?php echo date('F d, Y h:i A', strtotime($result['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Score:</strong> <?php echo $result['score']; ?>%</p>
                                <p><strong>Passing Score:</strong> <?php echo $result['passing_score']; ?>%</p>
                                <p><strong>Result:</strong> 
                                    <?php if ($result['score'] >= $result['passing_score']): ?>
                                        <span class="badge badge-success">Pass</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Fail</span>
                                    <?php endif; ?>
                                </p>
                                <?php 
                                // Check if there are open questions
                                $hasOpenQuestions = false;
                                $openQuestionsGraded = true;
                                
                                foreach ($studentAnswers as $answer) {
                                    if ($answer['question_type'] === 'open') {
                                        $hasOpenQuestions = true;
                                        // Check if this answer has been graded
                                        if (!isset($answer['graded_points'])) {
                                            $openQuestionsGraded = false;
                                        }
                                    }
                                }
                                
                                if ($hasOpenQuestions): 
                                ?>
                                <p><strong>Grading Status:</strong> 
                                    <?php if ($openQuestionsGraded): ?>
                                        <span class="badge badge-success">All Open Questions Graded</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Needs Grading</span>
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <a href="grade_open_questions.php?exam_id=<?php echo $result['exam_id']; ?>&stagiaire_id=<?php echo $result['stagiaire_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-pencil-alt"></i> <?php echo $openQuestionsGraded ? 'Edit Grades' : 'Grade Open Questions'; ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?> 