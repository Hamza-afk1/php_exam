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
require_once __DIR__ . '/../models/Result.php';
require_once __DIR__ . '/../models/Answer.php';

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
$resultModel = new Result();
$answerModel = new Answer();

// Get the exam ID from URL
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// If no valid exam ID, redirect to dashboard
if ($examId <= 0) {
    header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
    exit;
}

// Get exam details
$exam = $examModel->getById($examId);

// If exam doesn't exist, redirect to dashboard
if (!$exam) {
    header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
    exit;
}

// Get result for this exam
$result = $resultModel->getResultByStagiaireAndExam($stagiaireId, $examId);

// If no result found, redirect to dashboard (user hasn't taken the exam)
if (!$result) {
    header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
    exit;
}

// Get the answers for this exam
$answers = $answerModel->getAnswersByExamAndStagiaire($examId, $stagiaireId);

// Calculate the automatic score for QCM questions
$automaticScore = 0;
$openQuestionsCount = 0;
$totalPossiblePoints = 0;

foreach ($answers as $answer) {
    $totalPossiblePoints += $answer['points'];
    
    if ($answer['question_type'] === 'qcm') {
        if ($answer['is_correct']) {
            $automaticScore += $answer['points'];
        }
    } else {
        $openQuestionsCount++;
    }
}

// Page title
$pageTitle = "Exam Completed: " . $exam['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            padding-top: 70px;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 1000;
            padding: 20px 0;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #007bff;
        }
        main {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3 ml-auto">
            
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/stagiaire/exams.php">
                                <i class="fas fa-file-alt"></i> Available Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/stagiaire/results.php">
                                <i class="fas fa-chart-bar"></i> My Results
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Exam Completed</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4><?php echo htmlspecialchars($exam['name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Your exam has been submitted successfully!</h5>
                            <p>Thank you for completing the exam. Your answers have been recorded.</p>
                        </div>

                        <?php if ($result['score'] !== null): ?>
                            <!-- Exam has been graded -->
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Your Results</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <h2 class="display-4 <?php echo $result['score'] >= $exam['passing_score'] ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $result['score']; ?>%
                                            </h2>
                                            <p class="text-muted">Your Score</p>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h2 class="display-4"><?php echo $result['total_score']; ?>/<?php echo $exam['total_points']; ?></h2>
                                            <p class="text-muted">Points</p>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h2 class="display-4 <?php echo $result['score'] >= $exam['passing_score'] ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $result['score'] >= $exam['passing_score'] ? 'PASSED' : 'FAILED'; ?>
                                            </h2>
                                            <p class="text-muted">Result</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Exam is not graded yet -->
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Grading Status</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($openQuestionsCount > 0): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Your exam includes open-ended questions which need to be graded manually. Your final score will be available once the formateur completes the grading process.
                                        </div>
                                        <p>Currently graded QCM questions: <?php echo $automaticScore; ?> points</p>
                                        <p>Additional points from open-ended questions: Pending grading</p>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Your exam has been submitted and will be processed soon. Your final score will be available once the formateur completes the grading process.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>/stagiaire/results.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> View All Your Results
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>

