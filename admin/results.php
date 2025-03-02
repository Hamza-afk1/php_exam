<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../models/Result.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Exam.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is admin
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Initialize models
$resultModel = new Result();
$userModel = new User();
$examModel = new Exam();

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process form submissions
$message = '';
$error = '';

// Get message from query string (for redirects)
if (empty($message) && isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get result data for viewing
$resultData = null;
if ($action === 'view' && $resultId > 0) {
    // Get the result by ID
    $resultData = $resultModel->getById($resultId);
    
    if (!$resultData) {
        $error = "Result not found.";
        $action = 'list';
    } else {
        // Get related exam and user info
        $exam = $examModel->getById($resultData['exam_id']);
        $stagiaire = $userModel->getById($resultData['stagiaire_id']);
        
        // Get grader info if available
        $grader = null;
        if (!empty($resultData['graded_by'])) {
            $grader = $userModel->getById($resultData['graded_by']);
        }
    }
}

// Get all results with additional info
try {
    $db = new Database();
    $query = "SELECT r.*, e.name as exam_name, u.username as stagiaire_name, 
              g.username as grader_name
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              JOIN users u ON r.stagiaire_id = u.id
              LEFT JOIN users g ON r.graded_by = g.id
              ORDER BY r.created_at DESC";
    $stmt = $db->prepare($query);
    $db->execute($stmt);
    $results = $db->resultSet($stmt);
} catch (Exception $e) {
    $error = "Error loading results: " . $e->getMessage();
    $results = [];
}

// HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href='../assets/css/dark-mode.css' rel='stylesheet'>
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
        .score-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 24px;
            font-weight: bold;
            color: white;
        }
        .score-pass {
            background-color: #28a745;
        }
        .score-fail {
            background-color: #dc3545;
        }
    </style>
</head>
<body class="admin-page">
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3">
            
        </ul>
    </nav>    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/admin/index.php"><?php echo SITE_NAME; ?></a>
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/exams.php">
                                <i class="fas fa-clipboard-list"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/settings.php">
                                <i class="fas fa-cog"></i> Settings
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
                
                <?php if ($action === 'view' && $resultData): ?>
                    <!-- View Result Details -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Result Details</h4>
                            <a href="<?php echo BASE_URL; ?>/admin/results.php" class="btn btn-sm btn-secondary">
                                Back to All Results
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4">
                                    <div class="score-circle <?php echo $resultData['score'] >= $exam['passing_score'] ? 'score-pass' : 'score-fail'; ?>">
                                        <?php echo $resultData['score']; ?>%
                                    </div>
                                    <p class="mt-2">
                                        <span class="badge badge-<?php echo $resultData['score'] >= $exam['passing_score'] ? 'success' : 'danger'; ?>">
                                            <?php echo $resultData['score'] >= $exam['passing_score'] ? 'PASSED' : 'FAILED'; ?>
                                        </span>
                                    </p>
                                    <p>Passing score: <?php echo $exam['passing_score']; ?>%</p>
                                </div>
                                <div class="col-md-8">
                                    <h5><?php echo htmlspecialchars($exam['name']); ?></h5>
                                    <p><?php echo htmlspecialchars($exam['description']); ?></p>
                                    
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Stagiaire:</strong> <?php echo htmlspecialchars($stagiaire['username']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($stagiaire['email']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($resultData['created_at'])); ?></p>
                                            <p><strong>Graded by:</strong> 
                                                <?php echo $grader ? htmlspecialchars($grader['username']) : 'Automatic'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Additional Notes</h5>
                                <div class="alert alert-light">
                                    This section could display additional details about the exam results, 
                                    such as which questions were answered correctly/incorrectly, time taken for each question, 
                                    and any notes added by the grader.
                                </div>
                                
                                <div class="mt-4">
                                    <a href="<?php echo BASE_URL; ?>/admin/results.php" class="btn btn-secondary">
                                        Back to Results
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/admin/exams.php?action=view&id=<?php echo $resultData['exam_id']; ?>" class="btn btn-info">
                                        View Exam Details
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/admin/users.php?action=edit&id=<?php echo $resultData['stagiaire_id']; ?>" class="btn btn-primary">
                                        View Stagiaire Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Results List Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Stagiaire</th>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Graded By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($results)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No results found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?php echo $result['id']; ?></td>
                                            <td><?php echo htmlspecialchars($result['stagiaire_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                            <td><?php echo $result['score']; ?>%</td>
                                            <td>
                                                <?php
                                                // Get exam info to determine passing score
                                                $exam = $examModel->getById($result['exam_id']);
                                                $passingScore = $exam ? $exam['passing_score'] : 70;
                                                $isPassed = $result['score'] >= $passingScore;
                                                ?>
                                                <span class="badge badge-<?php echo $isPassed ? 'success' : 'danger'; ?>">
                                                    <?php echo $isPassed ? 'PASSED' : 'FAILED'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo !empty($result['grader_name']) ? htmlspecialchars($result['grader_name']) : 'Automatic'; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($result['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/admin/results.php?action=view&id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
</body>
</html>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
