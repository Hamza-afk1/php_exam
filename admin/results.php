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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin-theme.css" rel="stylesheet">
    <link href="../assets/css/dark-mode.css" rel="stylesheet">
    <style>
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 auto;
            color: var(--apple-text);
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.5) 100%);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .score-pass {
            background: linear-gradient(135deg, rgba(52,199,89,0.1) 0%, rgba(52,199,89,0.2) 100%);
            color: var(--apple-green);
        }
        
        .score-fail {
            background: linear-gradient(135deg, rgba(255,59,48,0.1) 0%, rgba(255,59,48,0.2) 100%);
            color: #ff3b30;
        }
        
        .result-card {
            transition: all 0.3s ease;
        }
        
        .result-card:hover {
            transform: translateY(-4px);
        }
        
        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
            border-radius: 20px;
        }
        
        .badge-success {
            background-color: var(--apple-green);
        }
        
        .badge-danger {
            background-color: #ff3b30;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/admin/index.php">
            <i class="fas fa-graduation-cap mr-2"></i><?php echo SITE_NAME; ?>
        </a>
        <div class="ml-auto">
            <button id="dark-mode-toggle" class="btn btn-outline-secondary">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/index.php">
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
                <div class="sidebar-footer">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="page-header d-flex justify-content-between align-items-center pt-3">
                    <h1>Exam Results</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'view' && $resultData): ?>
                    <div class="card result-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Result Details</h5>
                            <a href="<?php echo BASE_URL; ?>/admin/results.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Results
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4">
                                    <div class="score-circle <?php echo $resultData['score'] >= $exam['passing_score'] ? 'score-pass' : 'score-fail'; ?>">
                                        <?php echo $resultData['score']; ?>%
                                    </div>
                                    <p class="mt-3">
                                        <span class="badge badge-<?php echo $resultData['score'] >= $exam['passing_score'] ? 'success' : 'danger'; ?>">
                                            <?php echo $resultData['score'] >= $exam['passing_score'] ? 'PASSED' : 'FAILED'; ?>
                                        </span>
                                    </p>
                                    <p class="text-muted">Passing score: <?php echo $exam['passing_score']; ?>%</p>
                                </div>
                                <div class="col-md-8">
                                    <h4 class="mb-3"><?php echo htmlspecialchars($exam['name']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($exam['description']); ?></p>
                                    
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p>
                                                <i class="fas fa-user mr-2 text-muted"></i>
                                                <strong>Stagiaire:</strong> <?php echo htmlspecialchars($stagiaire['username']); ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-envelope mr-2 text-muted"></i>
                                                <strong>Email:</strong> <?php echo htmlspecialchars($stagiaire['email']); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p>
                                                <i class="fas fa-calendar mr-2 text-muted"></i>
                                                <strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($resultData['created_at'])); ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-user-check mr-2 text-muted"></i>
                                                <strong>Graded by:</strong> 
                                                <?php echo $grader ? htmlspecialchars($grader['username']) : 'Automatic'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <div class="btn-group">
                                            <a href="<?php echo BASE_URL; ?>/admin/exams.php?action=view&id=<?php echo $resultData['exam_id']; ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-clipboard-list mr-2"></i>View Exam
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/admin/users.php?action=edit&id=<?php echo $resultData['stagiaire_id']; ?>" 
                                               class="btn btn-outline-info">
                                                <i class="fas fa-user mr-2"></i>View Stagiaire
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
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
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['stagiaire_name']); ?></td>
                                                <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                                <td><?php echo $result['score']; ?>%</td>
                                                <td>
                                                    <span class="badge badge-<?php echo $result['score'] >= 70 ? 'success' : 'danger'; ?>">
                                                        <?php echo $result['score'] >= 70 ? 'PASSED' : 'FAILED'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $result['grader_name'] ? htmlspecialchars($result['grader_name']) : 'Automatic'; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($result['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo BASE_URL; ?>/admin/results.php?action=view&id=<?php echo $result['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
</body>
</html>