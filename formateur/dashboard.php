<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    echo "<div style='position: fixed; top: 10px; right: 10px; background-color: rgba(255,0,0,0.8); color: white; padding: 10px; border-radius: 5px; z-index: 10000; max-width: 80%; word-break: break-word;'>";
    echo "<strong>PHP Error:</strong><br>";
    echo "Error: [$errno] $errstr<br>";
    echo "File: $errfile Line: $errline<br>";
    echo "</div>";
    return true;
}
set_error_handler("customErrorHandler");

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../utils/Database.php'; // Include Database.php before model files
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Result.php';

// Start the session
Session::init();

// Check login status - redirect to login page if not logged in
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is formateur - redirect to main index if not formateur
if (Session::get('role') !== 'formateur') {
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

// Get statistics for the dashboard
try {
    // Get total exams created by this formateur
    $totalExams = $examModel->countExamsByFormateur($formateurId);

    // Get total questions created for this formateur's exams
    $totalQuestions = $examModel->countQuestionsByFormateur($formateurId);

    // Get total students who took this formateur's exams
    $totalStudents = $resultModel->countStudentsByFormateur($formateurId);
    
    // Get recent exam results
    $recentResults = $resultModel->getRecentResultsByFormateur($formateurId, 5);
    
    // Get exams that need grading
    $examsNeedingGrading = $examModel->getExamsNeedingGrading($formateurId);
    
    // Get highest and lowest scoring exams
    $examPerformance = $resultModel->getExamPerformanceByFormateur($formateurId);
    
} catch (Exception $e) {
    $error = "Error fetching dashboard data: " . $e->getMessage();
}

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Formateur Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <a href="exams.php?action=add" class="btn btn-sm btn-outline-secondary">Create New Exam</a>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">QUESTIONS CREATED</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalQuestions; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Exams Created</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalExams; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">TOTAL STUDENTS</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Results Column -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Exam Results</h6>
                    <a href="results.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentResults)): ?>
                        <p class="text-center text-muted">No results available yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentResults as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                            <td>
                                                <?php 
                                                    echo $result['score'] . '/' . $result['total_points'];
                                                    $percentage = ($result['score'] / $result['total_points']) * 100;
                                                    echo ' (' . round($percentage) . '%)';
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($result['submission_date'])); ?></td>
                                            <td>
                                                <a href="view_result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Exams Needing Grading Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Exams Needing Grading</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($examsNeedingGrading)): ?>
                        <p class="text-center text-muted">No exams need grading at the moment.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($examsNeedingGrading as $exam): ?>
                                <a href="grade_exam.php?exam_id=<?php echo $exam['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($exam['name']); ?></h6>
                                        <span class="badge badge-primary"><?php echo $exam['stagiaires_waiting']; ?> waiting</span>
                                    </div>
                                    <small><?php echo $exam['open_question_count']; ?> open questions</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Performance Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Exam Performance</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($examPerformance)): ?>
                        <p class="text-center text-muted">No performance data available yet.</p>
                    <?php else: ?>
                        <div class="small mb-2">
                            <p><strong>Highest Scoring Exam:</strong><br>
                            <?php if (isset($examPerformance['highest'])): ?>
                                <?php echo htmlspecialchars($examPerformance['highest']['name']); ?> 
                                (Avg: <?php echo round($examPerformance['highest']['avg_score'], 1); ?>%)
                            <?php else: ?>
                                No data available
                            <?php endif; ?>
                            </p>
                            
                            <p><strong>Lowest Scoring Exam:</strong><br>
                            <?php if (isset($examPerformance['lowest'])): ?>
                                <?php echo htmlspecialchars($examPerformance['lowest']['name']); ?> 
                                (Avg: <?php echo round($examPerformance['lowest']['avg_score'], 1); ?>%)
                            <?php else: ?>
                                No data available
                            <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <a href="results.php" class="btn btn-block btn-sm btn-outline-primary">View Detailed Analytics</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once __DIR__ . '/includes/footer_fixed.php';
?>
