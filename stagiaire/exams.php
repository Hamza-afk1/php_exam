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

// Get completed exams for this stagiaire
$completedResults = $resultModel->getAllStagiaireResults($stagiaireId);

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Available Exams</h1>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Available Exams Column -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Available Exams</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($availableExams)): ?>
                        <div class="no-exams-message">
                            <i class="fas fa-info-circle"></i>
                            <h3>No Available Exams</h3>
                            <p>You have completed all available exams. Check back later for new ones!</p>
                        </div>
                    <?php else: ?>
                        <div class="exam-list">
                            <?php foreach ($availableExams as $exam): ?>
                                <div class="card exam-card">
                                    <div class="card-header">
                                        <h5 class="card-title"><?php echo htmlspecialchars($exam['name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo htmlspecialchars(substr($exam['description'], 0, 150)) . (strlen($exam['description']) > 150 ? '...' : ''); ?></p>
                                        
                                        <div class="exam-info">
                                            <div class="exam-info-item">
                                                <i class="fas fa-clock"></i> <?php echo $exam['time_limit']; ?> minutes
                                            </div>
                                            <div class="exam-info-item">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($exam['formateur_name']); ?>
                                            </div>
                                            <div class="exam-info-item">
                                                <i class="fas fa-chart-bar"></i> Passing: <?php echo $exam['passing_score']; ?>%
                                            </div>
                                        </div>
                                        
                                        <a href="secure_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary exam-action-btn">
                                            <i class="fas fa-play-circle mr-1"></i> Start Exam
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Completed Exams Column -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Completed Exams</h6>
                    <a href="<?php echo BASE_URL; ?>/stagiaire/results.php" class="btn btn-sm btn-primary">View All Results</a>
                </div>
                <div class="card-body">
                    <?php if (empty($completedResults)): ?>
                        <p class="text-center text-muted my-4">
                            <i class="fas fa-info-circle mb-2 d-block" style="font-size: 2rem;"></i>
                            No completed exams yet.<br>Take your first exam to see your results here!
                        </p>
                    <?php else: ?>
                        <?php foreach (array_slice($completedResults, 0, 5) as $result): ?>
                            <div class="result-item">
                                <h6 class="mb-1"><?php echo htmlspecialchars($result['exam_name']); ?></h6>
                                <div class="result-details">
                                    <span class="result-score <?php echo $result['score'] >= $result['passing_score'] ? 'pass' : 'fail'; ?>">
                                        <?php echo $result['score']; ?>%
                                    </span>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($result['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Include footer
require_once __DIR__ . '/includes/footer_fixed.php';
?>
