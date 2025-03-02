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
$resultModel = new Result();

// Get result ID from query string (if viewing specific result)
$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

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
    
    // Verify the result is for an exam created by this formateur
    if ($resultDetail) {
        $exam = $examModel->getById($resultDetail['exam_id']);
        if (!$exam || $exam['formateur_id'] != $formateurId) {
            $error = "You do not have permission to view this result.";
            $resultDetail = null;
        }
    } else {
        $error = "Result not found.";
    }
}

// Get all exams for this formateur (for filter dropdown)
$formateur_exams = $examModel->getExamsByFormateurId($formateurId);

// Get all results for exams created by this formateur
if ($examId > 0) {
    // Verify this exam belongs to the formateur
    $exam = $examModel->getById($examId);
    if (!$exam || $exam['formateur_id'] != $formateurId) {
        $error = "You do not have permission to view results for this exam.";
        $results = [];
    } else {
        // Get results for specific exam
        $results = $resultModel->getResultsByExamId($examId);
    }
} else {
    // Get all results for all exams by this formateur
    try {
        $db = new Database();
        $query = "SELECT r.*, e.name as exam_name, u.username as stagiaire_name,
                         e.passing_score
                  FROM results r
                  JOIN exams e ON r.exam_id = e.id
                  JOIN users u ON r.stagiaire_id = u.id
                  WHERE e.formateur_id = :formateur_id
                  ORDER BY r.created_at DESC";
        $stmt = $db->prepare($query);
        $db->execute($stmt, [':formateur_id' => $formateurId]);
        $results = $db->resultSet($stmt);
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
        $results = [];
    }
}

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - <?php echo SITE_NAME; ?></title>
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
        .results-filter {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/formateur/dashboard.php"><?php echo SITE_NAME; ?></a>
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/exams.php">
                                <i class="fas fa-clipboard-list"></i> My Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/questions.php">
                                <i class="fas fa-question-circle"></i> Questions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/formateur/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/profile.php">
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
                    <h1 class="h2">Exam Results</h1>
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
                        <div class="card-header bg-primary text-white">
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
                                    <h5>Stagiaire Information</h5>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($resultDetail['stagiaire_name']); ?></p>
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
                            
                            <h5 class="mb-3">Question Responses</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Question</th>
                                            <th>Answer Given</th>
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
                                <a href="<?php echo BASE_URL; ?>/formateur/results.php<?php echo $examId ? '?exam_id=' . $examId : ''; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Results
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Results List -->
                    <div class="results-filter card mb-4">
                        <div class="card-body">
                            <form method="get" action="<?php echo BASE_URL; ?>/formateur/results.php" class="form-inline">
                                <div class="form-group mb-2">
                                    <label for="exam_id" class="mr-2">Filter by Exam:</label>
                                    <select class="form-control" id="exam_id" name="exam_id">
                                        <option value="">All Exams</option>
                                        <?php foreach ($formateur_exams as $exam): ?>
                                            <option value="<?php echo $exam['id']; ?>" <?php echo $examId == $exam['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($exam['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary mb-2 ml-2">Filter</button>
                                <?php if ($examId): ?>
                                    <a href="<?php echo BASE_URL; ?>/formateur/results.php" class="btn btn-outline-secondary mb-2 ml-2">
                                        Clear Filter
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                
                    <?php if (empty($results)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No results found.
                            <?php if ($examId): ?>
                                No stagiaires have taken this exam yet.
                            <?php else: ?>
                                No stagiaires have taken any of your exams yet.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Stagiaire</th>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th>Date Taken</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?php echo $result['id']; ?></td>
                                            <td><?php echo htmlspecialchars($result['stagiaire_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                            <td><?php echo $result['score']; ?>%</td>
                                            <td>
                                                <span class="badge badge-<?php echo $result['score'] >= $result['passing_score'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $result['score'] >= $result['passing_score'] ? 'PASSED' : 'FAILED'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/formateur/results.php?id=<?php echo $result['id']; ?><?php echo $examId ? '&exam_id=' . $examId : ''; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
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
