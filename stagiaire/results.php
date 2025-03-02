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

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-size: .875rem;
            padding-top: 4.5rem;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
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
        .cert-container {
            background-color: #ffffff;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            margin: 15px 0;
        }
        .cert-container:before {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            bottom: 10px;
            left: 10px;
            border: 1px dashed #28a745;
            border-radius: 5px;
            z-index: -1;
        }
        .cert-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .cert-title {
            font-size: 28px;
            color: #28a745;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .cert-subtitle {
            font-size: 16px;
            color: #6c757d;
        }
        .cert-body {
            text-align: center;
            margin-bottom: 20px;
        }
        .cert-name {
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0;
            color: #495057;
        }
        .cert-message {
            font-size: 16px;
            line-height: 1.6;
        }
        .cert-details {
            font-size: 14px;
            margin-top: 20px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
        }
        .cert-logo {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php"><?php echo SITE_NAME; ?></a>
        <ul class="navbar-nav px-3 ml-auto">
            <li class="nav-item text-nowrap mr-3">
                <button id="dark-mode-toggle" class="btn btn-outline-light">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/stagiaire/exams.php">
                                <i class="fas fa-clipboard-list"></i> My Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/stagiaire/results.php">
                                <i class="fas fa-chart-bar"></i> My Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/stagiaire/profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="sidebar-footer mt-auto position-absolute" style="bottom: 20px; width: 100%;">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php" style="padding: 0.75rem 1rem;">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Results</h1>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($resultDetail): ?>
                    <!-- View Detailed Result -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Result Details</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Exam Information</h5>
                                    <p><strong>Exam:</strong> <?php echo htmlspecialchars($resultDetail['exam_name']); ?></p>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($resultDetail['description'] ?? 'N/A'); ?></p>
                                    <p>
                                        <strong>Passing Score:</strong> <?php echo $resultDetail['passing_score']; ?>%
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Result Information</h5>
                                    <p><strong>Date Taken:</strong> <?php echo date('F j, Y g:i A', strtotime($resultDetail['created_at'])); ?></p>
                                    <p>
                                        <strong>Score:</strong> 
                                        <span class="badge badge-<?php echo $resultDetail['score'] >= $resultDetail['passing_score'] ? 'success' : 'danger'; ?>">
                                            <?php echo $resultDetail['score']; ?>%
                                        </span>
                                        <span class="ml-2">
                                            (<?php echo $resultDetail['score'] >= $resultDetail['passing_score'] ? 'PASSED' : 'FAILED'; ?>)
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Only show certificate if passed -->
                            <?php if ($resultDetail['score'] >= $resultDetail['passing_score']): ?>
                                <div class="cert-container">
                                    <div class="cert-header">
                                        <div class="cert-logo">
                                            <i class="fas fa-award fa-3x text-success"></i>
                                        </div>
                                        <div class="cert-title">Certificate of Completion</div>
                                        <div class="cert-subtitle"><?php echo SITE_NAME; ?></div>
                                    </div>
                                    <div class="cert-body">
                                        <p>This is to certify that</p>
                                        <div class="cert-name"><?php echo htmlspecialchars(Session::get('username')); ?></div>
                                        <p class="cert-message">
                                            has successfully completed the exam<br>
                                            <strong>"<?php echo htmlspecialchars($resultDetail['exam_name']); ?>"</strong><br>
                                            with a score of <strong><?php echo $resultDetail['score']; ?>%</strong>
                                        </p>
                                    </div>
                                    <div class="cert-details">
                                        <span>Date: <?php echo date('F j, Y', strtotime($resultDetail['created_at'])); ?></span>
                                        <span>Certificate ID: #<?php echo $resultDetail['id']; ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <h5 class="my-4">Question Responses</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Question</th>
                                            <th>Your Answer</th>
                                            <th>Correct Answer</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($resultDetail['answers']) && is_array($resultDetail['answers'])): ?>
                                            <?php foreach ($resultDetail['answers'] as $answer): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($answer['question_text']); ?></td>
                                                    <td><?php echo htmlspecialchars($answer['student_answer']); ?></td>
                                                    <td><?php echo htmlspecialchars($answer['correct_answer']); ?></td>
                                                    <td>
                                                        <?php if ($answer['is_correct']): ?>
                                                            <span class="text-success"><i class="fas fa-check"></i> Correct</span>
                                                        <?php else: ?>
                                                            <span class="text-danger"><i class="fas fa-times"></i> Incorrect</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Detailed answer data not available.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4">
                                <a href="<?php echo BASE_URL; ?>/stagiaire/results.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Results
                                </a>
                                <?php if ($resultDetail['score'] >= $resultDetail['passing_score']): ?>
                                    <button class="btn btn-success ml-2" onclick="window.print()">
                                        <i class="fas fa-print"></i> Print Certificate
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Results List -->
                    <?php if (empty($results)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You haven't completed any exams yet. 
                            <a href="<?php echo BASE_URL; ?>/stagiaire/exams.php">View available exams</a> to get started.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                    <?php endif; ?>
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
