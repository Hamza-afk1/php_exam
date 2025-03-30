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

// Get exam ID from URL
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Validate exam
if ($examId <= 0) {
    header('Location: ' . BASE_URL . '/formateur/dashboard.php');
    exit;
}

// Get exam details
$exam = $examModel->getById($examId);

// Verify the exam belongs to this formateur
if (!$exam || $exam['formateur_id'] != $formateurId) {
    header('Location: ' . BASE_URL . '/formateur/dashboard.php');
    exit;
}

// Get ungraded answers for this exam
$unansweredQuestions = $answerModel->getUngradedAnswersByExam($examId);

// Group answers by stagiaire
$stagiairesAnswers = [];
foreach ($unansweredQuestions as $answer) {
    $stagiairesAnswers[$answer['stagiaire_id']]['username'] = $answer['stagiaire_name'];
    $stagiairesAnswers[$answer['stagiaire_id']]['answers'][] = $answer;
}

// Process grading submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_answers'])) {
    $totalGraded = 0;
    $hasErrors = false;

    // Validate and process grading for each answer
    foreach ($_POST['graded_points'] as $answerId => $points) {
        // Validate points
        if ($points === '' || $points < 0) {
            $error = "Invalid points assigned. Please enter valid points.";
            $hasErrors = true;
            break;
        }

        // Update answer with graded points
        $updateData = [
            'graded_points' => (float)$points,
            'is_correct' => $points > 0 ? 1 : 0
        ];
        $answerModel->update($updateData, $answerId);
        $totalGraded++;
    }

    if (!$hasErrors) {
        // Recalculate total score for each stagiaire
        foreach (array_keys($stagiairesAnswers) as $stagiaireId) {
            $scoreData = $answerModel->calculateTotalScore($examId, $stagiaireId);
            
            // Update result
            $resultData = [
                'score' => $scoreData['percentage_score'],
                'total_score' => $scoreData['total_score'],
                'graded_by' => $formateurId
            ];
            $resultModel->updateResultByStagiaireAndExam($resultData, $stagiaireId, $examId);
        }

        $message = "{$totalGraded} answers graded successfully!";
        
        // Reload the page to show updated status
        header('Location: ' . BASE_URL . '/formateur/exam_grading.php?exam_id=' . $examId . '&message=' . urlencode($message));
        exit;
    }
}

// Page title
$pageTitle = "Grade Exam: " . $exam['name'];
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
            background-color:rgb(189, 188, 188);
            border-radius: 0.5rem;
            
        }
        .answer-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .answer-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/formateur/dashboard.php"><?php echo SITE_NAME; ?></a>
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
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/formateur/exams.php">
                                <i class="fas fa-file-alt"></i> My Exams
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-clipboard-check"></i> Grade Exam: <?php echo htmlspecialchars($exam['name']); ?>
                    </h1>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($stagiairesAnswers)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No open-ended questions need grading for this exam.
                    </div>
                <?php else: ?>
                    <form method="post" id="grading-form">
                        <?php foreach ($stagiairesAnswers as $stagiaireId => $stagiaireData): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user"></i> 
                                        Stagiaire: <?php echo htmlspecialchars($stagiaireData['username']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($stagiaireData['answers'] as $answer): ?>
                                        <div class="answer-card">
                                            <div class="answer-header">
                                                <strong>Question:</strong> 
                                                <?php echo htmlspecialchars($answer['question_text']); ?>
                                                <span class="badge badge-info float-right">
                                                    Max Points: <?php echo $answer['points']; ?>
                                                </span>
                                            </div>
                                            <div class="p-3">
                                                <p><strong>Answer:</strong> 
                                                    <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                </p>
                                                <div class="form-group">
                                                    <label for="points_<?php echo $answer['id']; ?>">
                                                        Points (0 - <?php echo $answer['points']; ?>)
                                                    </label>
                                                    <input type="number" 
                                                           class="form-control" 
                                                           id="points_<?php echo $answer['id']; ?>" 
                                                           name="graded_points[<?php echo $answer['id']; ?>]" 
                                                           min="0" 
                                                           max="<?php echo $answer['points']; ?>" 
                                                           step="0.5" 
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mb-5">
                            <button type="submit" name="grade_answers" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle"></i> Submit Grades
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Optional: Add client-side validation
        document.getElementById('grading-form').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[type="number"]');
            let isValid = true;

            inputs.forEach(input => {
                const max = parseFloat(input.max);
                const value = parseFloat(input.value);

                if (isNaN(value) || value < 0 || value > max) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please enter valid points for all questions.');
            }
        });
    </script>
</body>
</html>

<?php
// Include footer
require_once __DIR__ . '/includes/footer_fixed.php';
?>

