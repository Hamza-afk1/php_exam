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

// Get stagiaire info
$stagiaire = $userModel->getById($stagiaireId);

// Get available exams for this stagiaire (that they haven't taken yet)
try {
    $db = new Database();
    $query = "SELECT e.*, u.username as formateur_name
              FROM exams e
              JOIN users u ON e.formateur_id = u.id
              WHERE e.id NOT IN (
                  SELECT exam_id FROM results 
                  WHERE stagiaire_id = :stagiaire_id
              )
              ORDER BY e.created_at DESC";
    $stmt = $db->prepare($query);
    $db->execute($stmt, [':stagiaire_id' => $stagiaireId]);
    $availableExams = $db->resultSet($stmt);
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    $availableExams = [];
}

// Get recent results for this stagiaire
$recentResults = $resultModel->getAllStagiaireResults($stagiaireId);

// Count total exams completed
$completedExams = count($recentResults);

// Calculate average score
$totalScore = 0;
$passedExams = 0;
foreach ($recentResults as $result) {
    $totalScore += $result['score'];
    
    // Get passing score for this exam
    $exam = $examModel->getById($result['exam_id']);
    if ($exam && $result['score'] >= $exam['passing_score']) {
        $passedExams++;
    }
}
$avgScore = $completedExams > 0 ? round($totalScore / $completedExams, 1) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo SITE_NAME; ?></title>
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
            background-color:rgb()
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
        .exam-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            background-color: #007bff;
        }
        .support-card {
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .support-card:hover {
            transform: translateY(-5px);
        }
        .card-title {
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
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
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/stagiaire/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/stagiaire/exams.php">
                                <i class="fas fa-clipboard-list"></i> My Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/stagiaire/results.php">
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
                    <h1 class="h2">Student Dashboard</h1>
                    <div class="btn-group">
                        <a href="<?php echo BASE_URL; ?>/stagiaire/exams.php" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-clipboard-list"></i> View All Exams
                        </a>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-info text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Available Exams</h6>
                                        <h2 class="mb-0"><?php echo count($availableExams); ?></h2>
                                    </div>
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Completed Exams</h6>
                                        <h2 class="mb-0"><?php echo $completedExams; ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Average Score</h6>
                                        <h2 class="mb-0"><?php echo $avgScore; ?>%</h2>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Exams Section -->
                <h4 class="mb-3">Available Exams</h4>
                <?php if (empty($availableExams)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You have completed all available exams. Check with your instructor for more.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach (array_slice($availableExams, 0, 3) as $exam): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 exam-card bg-light">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($exam['name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted"><?php echo htmlspecialchars(substr($exam['description'], 0, 100)) . (strlen($exam['description']) > 100 ? '...' : ''); ?></p>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span><i class="fas fa-clock"></i> <?php echo $exam['time_limit']; ?> min</span>
                                            <span><i class="fas fa-check"></i> Pass: <?php echo $exam['passing_score']; ?>%</span>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($exam['formateur_name'] ?? 'Unknown'); ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <a href="<?php echo BASE_URL; ?>/exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-success btn-block">
                                            <i class="fas fa-play-circle"></i> Start Exam
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($availableExams) > 3): ?>
                        <div class="text-center mb-4">
                            <a href="<?php echo BASE_URL; ?>/stagiaire/exams.php" class="btn btn-outline-primary">
                                View All Available Exams (<?php echo count($availableExams); ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Recent Results Section -->
                <h4 class="mb-3">Recent Results</h4>
                <?php if (empty($recentResults)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You haven't taken any exams yet. Start an exam to see your results here.
                    </div>
                <?php else: ?>
                    <div class="card bg-light">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recentResults, 0, 5) as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                            <td><?php echo $result['score']; ?>%</td>
                                            <td>
                                                <?php
                                                // Get exam passing score
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
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (count($recentResults) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/stagiaire/results.php" class="btn btn-outline-primary">
                                View All Results
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Additional Resources Section -->
                <!--<div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card support-card bg-light">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-lightbulb"></i> Need Help?</h5>
                                <p class="card-text">If you need assistance with your exams or have questions, please contact your instructor or the administration.</p>
                                <a href="#" class="btn btn-outline-secondary">Contact Support</a>
                            </div>
                        </div>
                    </div>
                </div>-->
                
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
