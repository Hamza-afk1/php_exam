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
$openQuestionsExist = false;

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
                
                // Check if there are open-ended questions
                foreach ($answers as $answer) {
                    if ($answer['question_type'] === 'open') {
                        $openQuestionsExist = true;
                        break;
                    }
                }
                
                if (!$openQuestionsExist) {
                    $error = "This exam doesn't contain any open-ended questions to grade.";
                }
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
        
        // Update the result with the total score and mark as graded by this formateur
        $resultModel->updateResultByStagiaireAndExam([
            'score' => $totalScore,
            'graded_by' => $formateurId
        ], $stagiaireId, $examId);
        
        $message = "Exam graded successfully! The student's final score has been updated.";
        
        // Reload the page to show updated grades
        header('Location: ' . BASE_URL . '/formateur/grade_open_questions.php?exam_id=' . $examId . '&stagiaire_id=' . $stagiaireId . '&message=' . urlencode($message));
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
$pageTitle = "Grade Open Questions";
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
            border-left: 3px solid #6c757d;
        }
        .grading-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            border-left: 3px solid #28a745;
        }
        .score-summary {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .progress {
            height: 10px;
            margin-bottom: 5px;
        }
        .grade-input {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }
        .final-score {
            font-size: 24px;
            font-weight: bold;
        }
        .question-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .save-btn {
            position: sticky;
            bottom: 20px;
            z-index: 100;
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
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
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
                    <h1 class="h2"><i class="fas fa-pencil-alt mr-2"></i>Grade Open-Ended Questions</h1>
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
                    <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($examData && $stagiaireData && !empty($answers) && $openQuestionsExist): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($examData['name']); ?> - Grading for <?php echo htmlspecialchars($stagiaireData['username']); ?></h5>
                                <div>
                                    <span class="badge badge-light">Total Points: <?php echo $examData['total_points']; ?></span>
                                    <span class="badge badge-<?php echo ($result && $result['score'] >= $examData['passing_score']) ? 'success' : 'danger'; ?>">
                                        Current Score: <?php echo round($totalScore, 1); ?>/<?php echo $maxScore; ?> (<?php echo $result ? round($result['score'], 1) : 0; ?>%)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Score Summary -->
                            <div class="score-summary mb-4">
                                <h5>Score Summary</h5>
                                <div class="row">
                                    <div class="col-md-9">
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo ($result && $result['score'] >= $examData['passing_score']) ? 'success' : 'danger'; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo min(100, $result ? round($result['score'], 1) : 0); ?>%;" 
                                                 aria-valuenow="<?php echo $result ? round($result['score'], 1) : 0; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted">Passing score: <?php echo $examData['passing_score']; ?>%</small>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <span class="final-score"><?php echo $result ? round($result['score'], 1) : 0; ?>%</span>
                                    </div>
                                </div>
                            </div>

                            <form method="post" action="">
                                <?php 
                                $qcmScore = 0;
                                $openScore = 0;
                                $openQuestionsCount = 0;
                                $qcmQuestionsCount = 0;
                                
                                // Calculate automatic scores for QCM questions
                                foreach ($answers as $answer) {
                                    if ($answer['question_type'] === 'qcm' || $answer['question_type'] === 'true_false') {
                                        $qcmQuestionsCount++;
                                        if ($answer['is_correct']) {
                                            $qcmScore += $answer['points'];
                                        }
                                    } else if ($answer['question_type'] === 'open') {
                                        $openQuestionsCount++;
                                        if (isset($answer['graded_points'])) {
                                            $openScore += $answer['graded_points'];
                                        }
                                    }
                                }
                                ?>

                                <!-- QCM Score Summary -->
                                <?php if ($qcmQuestionsCount > 0): ?>
                                <div class="alert alert-info">
                                    <div class="row align-items-center">
                                        <div class="col-md-9">
                                            <h5 class="mb-0">Multiple Choice Questions (<?php echo $qcmQuestionsCount; ?> questions)</h5>
                                            <small>These questions are scored automatically</small>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            <strong>Score: <?php echo $qcmScore; ?> points</strong>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <h4 class="mt-4">Open-Ended Questions (<?php echo $openQuestionsCount; ?> questions)</h4>
                                <p class="text-muted">Please review each answer and assign points based on correctness and completeness.</p>
                                
                                <?php foreach ($answers as $answer): ?>
                                    <?php if ($answer['question_type'] === 'open'): ?>
                                        <div class="card question-card mb-4">
                                            <div class="card-header d-flex justify-content-between">
                                                <span><strong>Question <?php echo $answer['question_id']; ?>:</strong> <?php echo htmlspecialchars($answer['question_text']); ?></span>
                                                <span class="points-indicator">(Max: <?php echo $answer['points']; ?> points)</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="answer-box">
                                                    <h6><i class="fas fa-user-graduate mr-2"></i>Student's Answer:</h6>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?></p>
                                                </div>
                                                
                                                <div class="grading-box">
                                                    <h6><i class="fas fa-check-circle mr-2"></i>Grading:</h6>
                                                    <?php if (isset($answer['correct_answer']) && !empty($answer['correct_answer'])): ?>
                                                    <p><strong>Reference Answer:</strong> <?php echo htmlspecialchars($answer['correct_answer']); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <div class="form-group">
                                                        <label for="answer_<?php echo $answer['id']; ?>">
                                                            Points Awarded (0-<?php echo $answer['points']; ?>):
                                                        </label>
                                                        <input type="number" class="form-control grade-input" id="answer_<?php echo $answer['id']; ?>" 
                                                               name="answer[<?php echo $answer['id']; ?>]" 
                                                               min="0" max="<?php echo $answer['points']; ?>" step="0.5" 
                                                               value="<?php echo isset($answer['graded_points']) ? $answer['graded_points'] : 0; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <div class="form-group mt-4 text-center save-btn">
                                    <button type="submit" name="grade_exam" class="btn btn-lg btn-primary">
                                        <i class="fas fa-save mr-2"></i> Save Grades & Calculate Final Score
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($examData && $stagiaireData && !empty($answers) && !$openQuestionsExist): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle mr-2"></i>No Open-Ended Questions</h5>
                        <p>This exam only contains multiple choice questions which have been automatically graded.</p>
                        <a href="<?php echo BASE_URL; ?>/formateur/results.php?exam_id=<?php echo $examId; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Return to Results
                        </a>
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