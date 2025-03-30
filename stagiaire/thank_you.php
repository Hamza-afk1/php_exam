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
require_once __DIR__ . '/../models/Question.php';

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

// Get the stagiaire ID and name from session
$stagiaireId = Session::get('user_id');
$stagiaireName = Session::get('username');

// Initialize models
$userModel = new User();
$examModel = new Exam();
$resultModel = new Result();
$questionModel = new Question();

// Get the exam ID from URL
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Get exam details if ID provided
$exam = null;
if ($examId > 0) {
    $exam = $examModel->getById($examId);
}

// Get the result
$result = $resultModel->getResultByStagiaireAndExam($stagiaireId, $examId);
$passingScore = $exam['passing_score'] ?? 70;

// Check if exam has open-ended questions
$hasOpenQuestions = false;
$questions = $questionModel->getQuestionsByExamId($examId);
foreach ($questions as $question) {
    if ($question['question_type'] === 'open') {
        $hasOpenQuestions = true;
        break;
    }
}

// Auto-redirect to dashboard after 10 seconds
header("Refresh: 10;url=" . BASE_URL . "/stagiaire/dashboard.php");

// Page title
$pageTitle = "Thank You";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 60px;
        }
        .thank-you-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px;
        }
        .thank-you-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            background-color: white;
        }
        .thank-you-header {
            background-color: #28a745;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .thank-you-content {
            padding: 30px;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            color: white;
        }
        .countdown {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 38px;
            text-align: center;
            border: 2px solid #28a745;
            border-radius: 50%;
            color: #28a745;
            font-weight: bold;
            margin-top: 10px;
        }
        .action-btns {
            margin-top: 20px;
        }
        .action-btns .btn {
            margin: 0 5px;
        }
        .exam-details {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </nav>

    <div class="thank-you-container my-5">
        <div class="thank-you-card">
            <div class="thank-you-header">
                <i class="fas fa-check-circle success-icon animate__animated animate__bounceIn"></i>
                <h2 class="animate__animated animate__fadeInDown">Congratulations, <?php echo htmlspecialchars($stagiaireName); ?>!</h2>
                <p class="lead animate__animated animate__fadeInUp">Your exam has been successfully submitted.</p>
            </div>
            <div class="thank-you-content">
                <div class="text-center mb-4">
                    <p>Thank you for completing the exam. Your answers have been recorded and will be processed.</p>
                    
                    <?php if ($exam): ?>
                    <div class="exam-details">
                        <h5><?php echo htmlspecialchars($exam['name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars(substr($exam['description'], 0, 100)) . (strlen($exam['description']) > 100 ? '...' : ''); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($result): ?>
                        <div class="alert alert-<?php echo $result['score'] >= $passingScore ? 'success' : 'danger'; ?>">
                            <h5>Your Score: <?php echo $result['score']; ?>%</h5>
                            <p>Passing Score: <?php echo $passingScore; ?>%</p>
                            <p>Result: <strong><?php echo $result['score'] >= $passingScore ? 'Passed' : 'Failed'; ?></strong></p>
                            
                            <?php if ($hasOpenQuestions): ?>
                            <hr>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> Your exam includes open-ended questions that will be graded by your instructor. 
                                Your final score may be adjusted after grading is complete.
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="action-btns">
                        <a href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-home mr-1"></i> Go to Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>/stagiaire/results.php" class="btn btn-info">
                            <i class="fas fa-chart-bar mr-1"></i> View Results
                        </a>
                        <?php if ($examId): ?>
                        <a href="<?php echo BASE_URL; ?>/stagiaire/exam_complete.php?exam_id=<?php echo $examId; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-eye mr-1"></i> View Exam Details
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-muted">You will be redirected to the dashboard in <span id="countdown" class="countdown">10</span> seconds.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Countdown timer
        var seconds = 10;
        var countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
            }
        }
        
        var countdownInterval = setInterval(updateCountdown, 1000);
    </script>
</body>
</html> 