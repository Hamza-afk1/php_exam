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
$answerModel = new Answer();
$resultModel = new Result();

// Get parameters from request
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$stagiaireId = isset($_GET['stagiaire_id']) ? (int)$_GET['stagiaire_id'] : 0;

// Initialize variables
$message = '';
$error = '';
$examData = null;
$stagiaireData = null;
$questions = [];
$answers = [];
$totalScore = 0;
$maxScore = 0;

// Verify this exam belongs to the current formateur
if ($examId > 0) {
    $examData = $examModel->getById($examId);
    if (!$examData) {
        $error = "Exam not found.";
    } else if ($examData['formateur_id'] != $formateurId) {
        $error = "You do not have permission to access this exam.";
    } else {
        // Get the stagiaire info
        if ($stagiaireId > 0) {
            $stagiaireData = $userModel->getById($stagiaireId);
            if (!$stagiaireData || $stagiaireData['role'] !== 'stagiaire') {
                $error = "Stagiaire not found.";
            } else {
                // Get answers for this exam and stagiaire
                $answers = $answerModel->getAnswersByExamAndStagiaire($examId, $stagiaireId);
            }
        } else {
            $error = "Stagiaire ID is required.";
        }
    }
}

// Process grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_exam'])) {
    $hasErrors = false;
    $totalOpenScore = 0;
    $maxOpenScore = 0;
    
    // Validate grading for open-ended questions
    foreach ($answers as $answer) {
        if ($answer['question_type'] === 'open') {
            $questionId = $answer['question_id'];
            $maxPoints = $answer['points'];
            $maxOpenScore += $maxPoints;
            
            // Check if points were assigned
            if (!isset($_POST['answer'][$answer['id']]) || 
                $_POST['answer'][$answer['id']] === '' || 
                $_POST['answer'][$answer['id']] < 0 || 
                $_POST['answer'][$answer['id']] > $maxPoints) {
                $error = "Please assign valid points for all open-ended questions. Points must be between 0 and {$maxPoints}.";
                $hasErrors = true;
                break;
            }
            
            $gradedPoints = (float)$_POST['answer'][$answer['id']];
            $totalOpenScore += $gradedPoints;
            
            // Update the answer with graded points
            $updateData = [
                'graded_points' => $gradedPoints,
                'is_correct' => $gradedPoints > 0 ? 1 : 0
            ];
            $answerModel->gradeOpenAnswer($answer['id'], (int)$gradedPoints);
        }
    }
    
    if (!$hasErrors) {
        // Calculate the total score
        $scoreData = $answerModel->calculateTotalScore($examId, $stagiaireId);
        $totalScore = $scoreData['total_score'];
        
        // Update the result with the total score
        $answerModel->updateExamResult($examId, $stagiaireId, $totalScore, $examData['total_points']);
        
        $message = "Exam graded successfully!";
        
        // Reload the page to show updated grades
        header('Location: ' . BASE_URL . '/formateur/grade_exam.php?exam_id=' . $examId . '&stagiaire_id=' . $stagiaireId . '&message=' . urlencode($message));
        exit;
    }
}

// Get message from URL if redirected
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
}

// Get the results
$result = null;
if ($examId > 0 && $stagiaireId > 0) {
    $result = $resultModel->getResultByStagiaireAndExam($stagiaireId, $examId);
}

// Calculate the current total score
if (!empty($answers)) {
    $scoreData = $answerModel->calculateTotalScore($examId, $stagiaireId);
    $totalScore = $scoreData['total_score'];
    $maxScore = $examData['total_points'];
}

// Page title
$pageTitle = "Grade Exam";
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
        .table th {
            background-color: #f8f9fa;
        }
        .points-indicator {
            font-weight: bold;
            margin-left: 10px;
        }
        .answer-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .grading-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/formateur/dashboard.php"><?php echo SITE_NAME; ?></a>
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/exams.php">
                                <i class="fas fa-file-alt"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/formateur/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Grade Exam</h1>
                    <?php if ($examData): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo BASE_URL; ?>/formateur/results.php?exam_id=<?php echo $examId; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Results
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($examData && $stagiaireData && !empty($answers)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5><?php echo htmlspecialchars($examData['name']); ?> - Grading for <?php echo htmlspecialchars($stagiaireData['username']); ?></h5>
                                <div>
                                    <span class="badge badge-primary">Total Points: <?php echo $examData['total_points']; ?></span>
                                    <span class="badge badge-<?php echo ($result && $result['score'] >= $examData['passing_score']) ? 'success' : 'danger'; ?>">
                                        Current Score: <?php echo $totalScore; ?>/<?php echo $maxScore; ?> (<?php echo $result ? $result['score'] : 0; ?>%)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <?php 
                                $qcmScore = 0;
                                $openQuestionsExist = false;
                                ?>

                                <h4>Multiple Choice Questions</h4>
                                <?php foreach ($answers as $answer): ?>
                                    <?php if ($answer['question_type'] === 'qcm'): ?>
                                        <div class="card mb-3">
                                            <div class="card-header d-flex justify-content-between">
                                                <span>Question: <?php echo htmlspecialchars($answer['question_text']); ?></span>
                                                <span class="points-indicator">
                                                    <?php 
                                                    echo $answer['is_correct'] ? 
                                                        '<span class="text-success">Correct (' . $answer['points'] . ' points)</span>' : 
                                                        '<span class="text-danger">Incorrect (0 points)</span>'; 
                                                    
                                                    if ($answer['is_correct']) {
                                                        $qcmScore += $answer['points'];
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>Student's Answer:</strong> 
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
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <div class="alert alert-info">
                                    Total QCM Score: <?php echo $qcmScore; ?> points
                                </div>

                                <h4 class="mt-4">Open-Ended Questions</h4>
                                <?php foreach ($answers as $answer): ?>
                                    <?php if ($answer['question_type'] === 'open'): ?>
                                        <?php $openQuestionsExist = true; ?>
                                        <div class="card mb-3">
                                            <div class="card-header d-flex justify-content-between">
                                                <span>Question: <?php echo htmlspecialchars($answer['question_text']); ?></span>
                                                <span class="points-indicator">Max Points: <?php echo $answer['points']; ?></span>
                                            </div>
                                            <div class="card-body">
                                                <div class="answer-box">
                                                    <h6>Student's Answer:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?></p>
                                                </div>
                                                
                                                <div class="grading-box">
                                                    <h6>Grading:</h6>
                                                    <p><strong>Reference Answer:</strong> <?php echo htmlspecialchars($answer['correct_answer']); ?></p>
                                                    
                                                    <div class="form-group">
                                                        <label for="answer_<?php echo $answer['id']; ?>">
                                                            Points Awarded (0-<?php echo $answer['points']; ?>):
                                                        </label>
                                                        <input type="number" class="form-control" id="answer_<?php echo $answer['id']; ?>" 
                                                               name="answer[<?php echo $answer['id']; ?>]" 
                                                               min="0" max="<?php echo $answer['points']; ?>" 
                                                               value="<?php echo isset($answer['graded_points']) ? $answer['graded_points'] : 0; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <?php if ($openQuestionsExist): ?>
                                    <div class="form-group mt-4">
                                        <button type="submit" name="grade_exam" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Grades
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        This exam only contains multiple choice questions which have been automatically graded.
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
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

