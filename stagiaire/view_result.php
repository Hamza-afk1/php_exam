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
require_once __DIR__ . '/../models/Answer.php';
require_once __DIR__ . '/../models/Result.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is stagiaire
if (Session::get('user_role') !== 'stagiaire') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Get the stagiaire ID from session
$stagiaireId = Session::get('user_id');

// Initialize models
$userModel = new User();
$examModel = new Exam();
$questionModel = new Question();
$answerModel = new Answer();
$resultModel = new Result();

// Get the result ID from URL
$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$error = '';
$result = null;
$answers = [];
$openQuestionsGraded = true;

// Get the result
if ($resultId > 0) {
    $result = $resultModel->getById($resultId);
    
    // Verify this result belongs to the current stagiaire
    if (!$result || $result['stagiaire_id'] != $stagiaireId) {
        $error = "Result not found or you do not have permission to view it.";
    } else {
        // Get the exam
        $examId = $result['exam_id'];
        $exam = $examModel->getById($examId);
        
        if (!$exam) {
            $error = "Exam not found.";
        } else {
            // Get the answers
            $answers = $answerModel->getAnswersByExamAndStagiaire($examId, $stagiaireId);
            
            // Check if all open questions are graded
            foreach ($answers as $answer) {
                if ($answer['question_type'] === 'open' && !isset($answer['graded_points'])) {
                    $openQuestionsGraded = false;
                    break;
                }
            }
        }
    }
} else {
    $error = "Invalid result ID.";
}

// Page title
$pageTitle = "View Exam Result";

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-clipboard-check mr-2"></i> Exam Result</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php elseif ($result): ?>
                        <!-- Result Summary -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Result Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4><?php echo htmlspecialchars($exam['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($exam['description']); ?></p>
                                        <p><strong>Date Taken:</strong> <?php echo date('F d, Y h:i A', strtotime($result['created_at'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h2 class="mb-0"><?php echo round($result['score'], 1); ?>%</h2>
                                            <p class="lead mb-0">
                                                <?php if ($result['score'] >= $exam['passing_score']): ?>
                                                    <span class="badge badge-success">Pass</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Fail</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-muted">Passing Score: <?php echo $exam['passing_score']; ?>%</p>
                                            
                                            <?php if (!$openQuestionsGraded): ?>
                                                <div class="alert alert-warning mt-2 mb-0">
                                                    <i class="fas fa-exclamation-circle mr-2"></i> Some open-ended questions are still being graded by your instructor. Your final score may change.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-<?php echo ($result['score'] >= $exam['passing_score']) ? 'success' : 'danger'; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo min(100, $result['score']); ?>%;" 
                                         aria-valuenow="<?php echo $result['score']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Answer Details -->
                        <h4 class="mt-4 mb-3">Your Answers</h4>
                        
                        <?php
                        // Group answers by question type for better organization
                        $qcmAnswers = [];
                        $trueFalseAnswers = [];
                        $openAnswers = [];
                        
                        foreach ($answers as $answer) {
                            if ($answer['question_type'] === 'qcm') {
                                $qcmAnswers[] = $answer;
                            } elseif ($answer['question_type'] === 'true_false') {
                                $trueFalseAnswers[] = $answer;
                            } elseif ($answer['question_type'] === 'open') {
                                $openAnswers[] = $answer;
                            }
                        }
                        ?>
                        
                        <!-- Multiple Choice Questions -->
                        <?php if (!empty($qcmAnswers)): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Multiple Choice Questions</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($qcmAnswers as $answer): ?>
                                        <div class="mb-4 pb-3 border-bottom">
                                            <h6><?php echo htmlspecialchars($answer['question_text']); ?></h6>
                                            <div class="row">
                                                <div class="col-md-9">
                                                    <p><strong>Your Answer:</strong> 
                                                        <?php 
                                                        if (is_array($answer['answer_text'])) {
                                                            echo implode(', ', $answer['answer_text']);
                                                        } else {
                                                            echo htmlspecialchars($answer['answer_text']);
                                                        }
                                                        ?>
                                                    </p>
                                                    <p><strong>Correct Answer:</strong> 
                                                        <?php 
                                                        if (is_array($answer['correct_answer'])) {
                                                            echo implode(', ', $answer['correct_answer']);
                                                        } else {
                                                            echo htmlspecialchars($answer['correct_answer']);
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-3 text-right">
                                                    <?php if ($answer['is_correct']): ?>
                                                        <span class="badge badge-success">Correct</span>
                                                        <p class="mt-1"><?php echo $answer['points']; ?> points</p>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Incorrect</span>
                                                        <p class="mt-1">0 points</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- True/False Questions -->
                        <?php if (!empty($trueFalseAnswers)): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">True/False Questions</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($trueFalseAnswers as $answer): ?>
                                        <div class="mb-4 pb-3 border-bottom">
                                            <h6><?php echo htmlspecialchars($answer['question_text']); ?></h6>
                                            <div class="row">
                                                <div class="col-md-9">
                                                    <p><strong>Your Answer:</strong> <?php echo htmlspecialchars($answer['answer_text']); ?></p>
                                                    <p><strong>Correct Answer:</strong> <?php echo htmlspecialchars($answer['correct_answer']); ?></p>
                                                </div>
                                                <div class="col-md-3 text-right">
                                                    <?php if ($answer['is_correct']): ?>
                                                        <span class="badge badge-success">Correct</span>
                                                        <p class="mt-1"><?php echo $answer['points']; ?> points</p>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Incorrect</span>
                                                        <p class="mt-1">0 points</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Open-Ended Questions -->
                        <?php if (!empty($openAnswers)): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Open-Ended Questions</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($openAnswers as $answer): ?>
                                        <div class="mb-4 pb-3 border-bottom">
                                            <h6><?php echo htmlspecialchars($answer['question_text']); ?></h6>
                                            <div class="p-3 bg-light rounded mb-3">
                                                <p><strong>Your Answer:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?></p>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-9">
                                                    <?php if (isset($answer['correct_answer']) && !empty($answer['correct_answer'])): ?>
                                                    <p><strong>Reference Answer:</strong> <?php echo htmlspecialchars($answer['correct_answer']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-3 text-right">
                                                    <?php if (isset($answer['graded_points'])): ?>
                                                        <div class="alert alert-info mb-0">
                                                            <strong>Score: <?php echo $answer['graded_points']; ?> / <?php echo $answer['points']; ?> points</strong>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning mb-0">
                                                            <strong>Pending Grading</strong>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home mr-2"></i> Return to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?> 