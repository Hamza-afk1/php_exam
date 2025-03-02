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

// Get formateur info
$formateur = $userModel->getById($formateurId);

// Get exams created by this formateur
$exams = $examModel->getExamsByFormateurId($formateurId);

// Get exams that need grading
$ungraduatedExams = $examModel->getExamsNeedingGrading($formateurId);

// Get recent results for exams by this formateur
try {
    $db = new Database();
    $query = "SELECT r.*, e.name as exam_name, u.username as stagiaire_name
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              JOIN users u ON r.stagiaire_id = u.id
              WHERE e.formateur_id = :formateur_id
              ORDER BY r.created_at DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $db->execute($stmt, [':formateur_id' => $formateurId]);
    $recentResults = $db->resultSet($stmt);
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    $recentResults = [];
}

// Count students who took exams
try {
    $db = new Database();
    $query = "SELECT COUNT(DISTINCT stagiaire_id) as total_students
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              WHERE e.formateur_id = :formateur_id";
    $stmt = $db->prepare($query);
    $db->execute($stmt, [':formateur_id' => $formateurId]);
    $totalStudents = $db->single($stmt)['total_students'] ?? 0;
} catch (Exception $e) {
    $totalStudents = 0;
}

// Count total number of exam attempts
try {
    $db = new Database();
    $query = "SELECT COUNT(*) as total_attempts
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              WHERE e.formateur_id = :formateur_id";
    $stmt = $db->prepare($query);
    $db->execute($stmt, [':formateur_id' => $formateurId]);
    $totalAttempts = $db->single($stmt)['total_attempts'] ?? 0;
} catch (Exception $e) {
    $totalAttempts = 0;
}

// Get average score
try {
    $db = new Database();
    $query = "SELECT AVG(score) as avg_score
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              WHERE e.formateur_id = :formateur_id";
    $stmt = $db->prepare($query);
    $db->execute($stmt, [':formateur_id' => $formateurId]);
    $avgScore = round($db->single($stmt)['avg_score'] ?? 0, 1);
} catch (Exception $e) {
    $avgScore = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formateur Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dark-mode.css" rel="stylesheet">
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
        .stat-card {
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <a class="navbar-brand" href="#">Formateur Dashboard</a>
        <div class="ml-auto">
            <button id="dark-mode-toggle" class="btn btn-outline-secondary">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>
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
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/formateur/dashboard.php">
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/formateur/results.php">
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
                    <h1 class="h2">Formateur Dashboard</h1>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Exams</h6>
                                        <h2 class="mb-0"><?php echo count($exams); ?></h2>
                                    </div>
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Students</h6>
                                        <h2 class="mb-0"><?php echo $totalStudents; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Exam Attempts</h6>
                                        <h2 class="mb-0"><?php echo $totalAttempts; ?></h2>
                                    </div>
                                    <i class="fas fa-pen-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Avg. Score</h6>
                                        <h2 class="mb-0"><?php echo $avgScore; ?>%</h2>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> My Exams</h5>
                                    <a href="<?php echo BASE_URL; ?>/formateur/exams.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($exams)): ?>
                                    <p class="text-center">No exams created yet.</p>
                                    <div class="text-center">
                                        <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=add" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create New Exam
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach (array_slice($exams, 0, 5) as $exam): ?>
                                            <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=view&id=<?php echo $exam['id']; ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($exam['name']); ?></h6>
                                                    <small><?php echo date('M d, Y', strtotime($exam['created_at'])); ?></small>
                                                </div>
                                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars(substr($exam['description'], 0, 100)) . (strlen($exam['description']) > 100 ? '...' : ''); ?></p>
                                                <div>
                                                    <span class="badge badge-primary"><?php echo $exam['time_limit']; ?> min</span>
                                                    <span class="badge badge-info">Pass: <?php echo $exam['passing_score']; ?>%</span>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($exams) > 5): ?>
                                        <div class="text-center mt-3">
                                            <a href="<?php echo BASE_URL; ?>/formateur/exams.php" class="btn btn-sm btn-outline-primary">View All Exams</a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Recent Results</h5>
                                    <a href="<?php echo BASE_URL; ?>/formateur/results.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentResults)): ?>
                                    <p class="text-center">No results available yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Exam</th>
                                                    <th>Score</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentResults as $result): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($result['stagiaire_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                                        <td>
                                                            <?php 
                                                                // Get exam info to determine passing score
                                                                $exam = $examModel->getById($result['exam_id']);
                                                                $passingScore = $exam ? $exam['passing_score'] : 70;
                                                                $isPassed = $result['score'] >= $passingScore;
                                                            ?>
                                                            <span class="badge badge-<?php echo $isPassed ? 'success' : 'danger'; ?>">
                                                                <?php echo $result['score']; ?>%
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-clipboard-check"></i> Exams Needing Grading 
                                    <?php if (!empty($ungraduatedExams)): ?>
                                        <span class="badge badge-light ml-2"><?php echo count($ungraduatedExams); ?></span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($ungraduatedExams)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Exam Name</th>
                                                    <th>Open Questions</th>
                                                    <th>Stagiaires Waiting</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ungraduatedExams as $exam): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($exam['name']); ?></td>
                                                        <td><?php echo $exam['open_question_count']; ?></td>
                                                        <td><?php echo $exam['stagiaires_waiting']; ?></td>
                                                        <td>
                                                            <a href="<?php echo BASE_URL; ?>/formateur/exam_grading.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-edit"></i> Grade Exam
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No exams currently need grading.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center mb-3">
                                        <a href="<?php echo BASE_URL; ?>/formateur/exams.php?action=add" class="btn btn-lg btn-outline-primary w-100">
                                            <i class="fas fa-plus-circle mb-2 d-block" style="font-size: 2rem;"></i>
                                            Create New Exam
                                        </a>
                                    </div>
                                    <div class="col-md-4 text-center mb-3">
                                        <a href="<?php echo BASE_URL; ?>/formateur/questions.php" class="btn btn-lg btn-outline-success w-100">
                                            <i class="fas fa-question-circle mb-2 d-block" style="font-size: 2rem;"></i>
                                            Manage Questions
                                        </a>
                                    </div>
                                    <div class="col-md-4 text-center mb-3">
                                        <a href="<?php echo BASE_URL; ?>/formateur/results.php" class="btn btn-lg btn-outline-info w-100">
                                            <i class="fas fa-chart-pie mb-2 d-block" style="font-size: 2rem;"></i>
                                            View Analytics
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
