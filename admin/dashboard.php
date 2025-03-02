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

// Check if user is admin
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Get user data
$username = Session::get('username');
$email = Session::get('user_email');

// Get statistics (safely)
try {
    $userModel = new User();
    $examModel = new Exam();
    $resultModel = new Result();
    
    $formateurCount = count($userModel->getUsersByRole('formateur'));
    $stagiaireCount = count($userModel->getUsersByRole('stagiaire'));
    $examCount = count($examModel->getAll());
    
    // Get recent results safely
    $recentResults = [];
    try {
        // Use the proper getRecentResults method or execute a custom query
        if (method_exists($resultModel, 'getRecentResults')) {
            $recentResults = $resultModel->getRecentResults(5);
        } else {
            // Create a custom query that doesn't directly access protected properties
            $db = new Database();
            $query = "SELECT r.*, e.name as exam_name, u.username as stagiaire_name 
                    FROM results r
                    JOIN exams e ON r.exam_id = e.id
                    JOIN users u ON r.stagiaire_id = u.id
                    ORDER BY r.created_at DESC LIMIT 5";
            $stmt = $db->prepare($query);
            $db->execute($stmt);
            $recentResults = $db->resultSet($stmt);
        }
    } catch (Exception $e) {
        // Silently fail and continue with empty results
    }
} catch (Exception $e) {
    // Just continue with default values if there's an error
    $formateurCount = 0;
    $stagiaireCount = 0;
    $examCount = 0;
    $recentResults = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
    </style>
</head>
<body class="admin-page bg-gray-100">
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><?php echo SITE_NAME; ?></a>
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
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
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
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>System</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin_direct.php">
                                <i class="fas fa-tachometer-alt"></i> Alternative Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/debug_admin.php">
                                <i class="fas fa-bug"></i> Debug Page
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/basic.php">
                                <i class="fas fa-file-code"></i> Basic Admin Page
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
                    <h1 class="h2">Admin Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group mr-2">
                            <button id="dark-mode-toggle" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-moon"></i> Dark Mode
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Quick Access Cards -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Formateurs</h5>
                                <p class="card-text display-4 text-primary"><?php echo $formateurCount; ?></p>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php?role=formateur" class="btn btn-link">View all formateurs</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Stagiaires</h5>
                                <p class="card-text display-4 text-success"><?php echo $stagiaireCount; ?></p>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php?role=stagiaire" class="btn btn-link">View all stagiaires</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Exams</h5>
                                <p class="card-text display-4 text-warning"><?php echo $examCount; ?></p>
                                <a href="<?php echo BASE_URL; ?>/admin/exams.php" class="btn btn-link">View all exams</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results Table -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Results</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Stagiaire</th>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentResults as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['id']); ?></td>
                                        <td><?php echo htmlspecialchars($result['stagiaire_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                        <td><?php echo number_format($result['score'], 2) . '%'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">System Information</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">PHP Version</dt>
                            <dd class="col-sm-9"><?php echo phpversion(); ?></dd>

                            <dt class="col-sm-3">Database</dt>
                            <dd class="col-sm-9">MySQL <?php 
                                try {
                                    $db = new Database();
                                    $version = $db->query('SELECT VERSION()')->fetchColumn();
                                    echo htmlspecialchars($version);
                                } catch (Exception $e) {
                                    echo 'Unable to retrieve version';
                                }
                            ?></dd>

                            <dt class="col-sm-3">Server Software</dt>
                            <dd class="col-sm-9"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></dd>

                            <dt class="col-sm-3">Server Time</dt>
                            <dd class="col-sm-9"><?php echo date('Y-m-d H:i:s T'); ?></dd>
                        </dl>
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
