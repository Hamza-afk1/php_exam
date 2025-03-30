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
$resultModel = new Result();

// Get result ID from query string (if viewing specific result)
$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process form submissions
$message = '';
$error = '';

// Get message from query string (for redirects)
if (empty($message) && isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get detailed result data if viewing a specific result
$resultDetail = null;
if ($resultId > 0) {
    $resultDetail = $resultModel->getResultDetailById($resultId);
    
    // Verify this result belongs to the current stagiaire
    if ($resultDetail && $resultDetail['stagiaire_id'] != $stagiaireId) {
        $error = "You do not have permission to view this result.";
        $resultDetail = null;
    } else if (!$resultDetail) {
        $error = "Result not found.";
    }
}

// Get all results for this stagiaire
$results = $resultModel->getAllStagiaireResults($stagiaireId);

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

<div id="mainContent" class="main-content">
    <div class="content-header">
        <h1><i class="fas fa-chart-bar"></i> My Results</h1>
        <p>View your exam performance and detailed results</p>
                </div>
                
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="background-color: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid #dc3545; font-weight: 500;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
                <?php endif; ?>
                
    <?php if (!empty($message)): ?>
        <div class="alert alert-success" style="background-color: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid #28a745; font-weight: 500;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
                <?php endif; ?>
                
                <?php if ($resultDetail): ?>
        <!-- View specific result details -->
                    <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Exam Result: <?php echo htmlspecialchars($resultDetail['exam_name']); ?></h5>
                        </div>
                        <div class="card-body">
                <div class="row">
                                <div class="col-md-6">
                        <h6>Exam Information</h6>
                        <p><strong>Exam Name:</strong> <?php echo htmlspecialchars($resultDetail['exam_name']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($resultDetail['category_name']); ?></p>
                        <p><strong>Date Taken:</strong> <?php echo date('F j, Y, g:i a', strtotime($resultDetail['created_at'])); ?></p>
                                </div>
                                <div class="col-md-6">
                        <h6>Result Summary</h6>
                        <?php
                        // Get passing score from exam
                        $exam = $examModel->getById($resultDetail['exam_id']);
                        $passingScore = $exam ? $exam['passing_score'] : 70;
                        $isPassed = $resultDetail['score'] >= $passingScore;
                        ?>
                        <p><strong>Score:</strong> 
                            <span class="badge badge-<?php echo $isPassed ? 'success' : 'danger'; ?> p-2">
                                            <?php echo $resultDetail['score']; ?>%
                                        </span>
                        </p>
                        <p><strong>Status:</strong> 
                            <span class="badge badge-<?php echo $isPassed ? 'success' : 'danger'; ?> p-2">
                                <?php echo $isPassed ? 'PASSED' : 'FAILED'; ?>
                                        </span>
                                    </p>
                        <p><strong>Required to Pass:</strong> <?php echo $passingScore; ?>%</p>
                                </div>
                            </div>
                            
                <div class="mt-4">
                    <h6>Questions and Answers</h6>
                    <?php if (isset($resultDetail['questions']) && is_array($resultDetail['questions'])): ?>
                        <div class="list-group">
                            <?php foreach ($resultDetail['questions'] as $index => $question): ?>
                                <div class="list-group-item list-group-item-action flex-column align-items-start">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Question <?php echo $index + 1; ?>: <?php echo htmlspecialchars($question['question_text']); ?></h6>
                                        <small class="text-<?php echo $question['is_correct'] ? 'success' : 'danger'; ?>">
                                            <?php echo $question['is_correct'] ? '<i class="fas fa-check"></i> Correct' : '<i class="fas fa-times"></i> Incorrect'; ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><strong>Your Answer:</strong> <?php echo htmlspecialchars($question['user_answer']); ?></p>
                                    <?php if (!$question['is_correct']): ?>
                                        <p class="mb-0 text-success"><strong>Correct Answer:</strong> <?php echo htmlspecialchars($question['correct_answer']); ?></p>
                                    <?php endif; ?>
                                </div>
                                            <?php endforeach; ?>
                        </div>
                                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Detailed question data is not available for this result.
                        </div>
                                        <?php endif; ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="<?php echo BASE_URL; ?>/stagiaire/results.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to All Results
                    </a>
                    <!--<?php if ($isPassed): ?>
                        <a href="#" class="btn btn-success">
                            <i class="fas fa-award"></i> Download Certificate
                        </a>
                    <?php endif; ?>-->
                            </div>
                        </div>
                    </div>
                <?php else: ?>
        <!-- List all results -->
                    <?php if (empty($results)): ?>
            <div class="alert alert-info" style="background-color: rgba(0, 123, 255, 0.15); color: #0056b3; border: 1px solid #0056b3; font-weight: 500;">
                            <i class="fas fa-info-circle"></i> You haven't completed any exams yet. 
                        </div>
                    <?php else: ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Results</h5>
                </div>
                <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th>Date Completed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                            <td><?php echo $result['score']; ?>%</td>
                                            <td>
                                                <?php
                                                // Get passing score from exam
                                                $exam = $examModel->getById($result['exam_id']);
                                                $passingScore = $exam ? $exam['passing_score'] : 70;
                                                $isPassed = $result['score'] >= $passingScore;
                                                ?>
                                                <span class="badge badge-<?php echo $isPassed ? 'success' : 'danger'; ?>">
                                                    <?php echo $isPassed ? 'PASSED' : 'FAILED'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/stagiaire/results.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                               <!-- <?php if ($isPassed): ?>
                                                    <a href="<?php echo BASE_URL; ?>/stagiaire/results.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-award"></i> Certificate
                                                    </a>
                                                <?php endif; ?>-->
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    </div>
                </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
    </div>

<?php
// Include footer
require_once __DIR__ . '/includes/footer_fixed.php';
?>
