<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../utils/Database.php';
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

// Get exams for this formateur (for filtering)
$exams = $examModel->getExamsByFormateurId($formateurId);

// Handle filtering
$examFilter = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

try {
    $db = new Database();
    
    // Build the query with filters
    $query = "SELECT r.*, e.name as exam_name, u.username as stagiaire_name,
                     COALESCE(e.passing_score, 70) as passing_score
              FROM results r
              JOIN exams e ON r.exam_id = e.id
              JOIN users u ON r.stagiaire_id = u.id
              WHERE e.formateur_id = :formateur_id";
    
    $params = [':formateur_id' => $formateurId];
    
    // Add exam filter if specified
    if ($examFilter > 0) {
        $query .= " AND r.exam_id = :exam_id";
        $params[':exam_id'] = $examFilter;
    }
    
    // Add date filter if specified
    if (!empty($dateFilter)) {
        $query .= " AND DATE(r.created_at) = :date";
        $params[':date'] = $dateFilter;
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $db->execute($stmt, $params);
    $results = $db->resultSet($stmt);
    
    // Get statistics
    // Average score
    $avgQuery = "SELECT AVG(r.score) as avg_score
                FROM results r
                JOIN exams e ON r.exam_id = e.id
                WHERE e.formateur_id = :formateur_id";
    $stmt = $db->prepare($avgQuery);
    $db->execute($stmt, [':formateur_id' => $formateurId]);
    $avgScore = round($db->single($stmt)['avg_score'] ?? 0, 1);
    
    // Pass rate
    $passQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN r.score >= COALESCE(e.passing_score, 70) THEN 1 ELSE 0 END) as passed
                  FROM results r
                  JOIN exams e ON r.exam_id = e.id
                  WHERE e.formateur_id = :formateur_id";
    $stmt = $db->prepare($passQuery);
    $db->execute($stmt, [':formateur_id' => $formateurId]);
    $passData = $db->single($stmt);
    $passRate = $passData['total'] > 0 ? round(($passData['passed'] / $passData['total']) * 100, 1) : 0;
    
    // Total students
    $studentsQuery = "SELECT COUNT(DISTINCT r.stagiaire_id) as student_count
                     FROM results r
                     JOIN exams e ON r.exam_id = e.id
                     WHERE e.formateur_id = :formateur_id";
    $stmt = $db->prepare($studentsQuery);
    $db->execute($stmt, [':formateur_id' => $formateurId]);
    $studentCount = $db->single($stmt)['student_count'] ?? 0;
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    $results = [];
    $avgScore = 0;
    $passRate = 0;
    $studentCount = 0;
}

// Include header
require_once __DIR__ . '/includes/header_fixed.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-chart-bar mr-2"></i> Exam Results
    </h1>
    <p class="page-subtitle">View and analyze student performance</p>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="dashboard-stats mb-4">
    <div class="stat-card">
        <i class="fas fa-file-alt stat-icon"></i>
        <div class="stat-value"><?php echo count($exams); ?></div>
        <div class="stat-label">Total Exams</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-users stat-icon"></i>
        <div class="stat-value"><?php echo $studentCount; ?></div>
        <div class="stat-label">Students</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-percentage stat-icon"></i>
        <div class="stat-value"><?php echo $avgScore; ?>%</div>
        <div class="stat-label">Average Score</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle stat-icon"></i>
        <div class="stat-value"><?php echo $passRate; ?>%</div>
        <div class="stat-label">Pass Rate</div>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter mr-2"></i> Filter Results</h5>
    </div>
    <div class="card-body">
        <form method="get" action="results.php" class="form-inline">
            <div class="form-group mr-3 mb-2">
                <label for="exam_id" class="mr-2">Exam:</label>
                <select class="form-control" id="exam_id" name="exam_id">
                    <option value="0">All Exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['id']; ?>" <?php echo $examFilter == $exam['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($exam['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mr-3 mb-2">
                <label for="date" class="mr-2">Date:</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo $dateFilter; ?>">
            </div>
            <button type="submit" class="btn btn-primary mb-2 mr-2">
                <i class="fas fa-search mr-1"></i> Apply Filter
            </button>
            <a href="results.php" class="btn btn-outline-secondary mb-2">
                <i class="fas fa-sync-alt mr-1"></i> Reset
            </a>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table mr-2"></i> Exam Results</h5>
        <div>
            <a href="#" class="btn btn-sm btn-outline-primary" id="export-results">
                <i class="fas fa-file-download mr-1"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($results)): ?>
            <div class="empty-state">
                <i class="fas fa-chart-bar empty-state-icon"></i>
                <p class="empty-state-text">No results found for the selected filters.</p>
                <p>Try changing your filter criteria or check back after students have taken your exams.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="results-table">
                    <thead>
                        <tr>
                            <th>Student</th>
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
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php 
                                        if (!empty($result['stagiaire_name'])) {
                                            echo htmlspecialchars($result['stagiaire_name']);
                                        } else {
                                            echo '<em class="text-muted">Unknown</em>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $result['score'] >= $result['passing_score'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $result['score']; ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if ($result['score'] >= $result['passing_score']): ?>
                                        <span class="badge badge-success">Pass</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Fail</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($result['created_at'])); ?></td>
                                <td>
                                    <a href="view_result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="grade_exam.php?exam_id=<?php echo $result['exam_id']; ?>&stagiaire_id=<?php echo $result['stagiaire_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-pencil-alt"></i> Grade Open Questions
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Export results to CSV
    document.getElementById('export-results').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get the table
        const table = document.getElementById('results-table');
        if (!table) return;
        
        let csv = [];
        
        // Add headers
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        csv.push(headers.slice(0, -1).join(',')); // Exclude the Actions column
        
        // Add rows
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            const cells = Array.from(row.querySelectorAll('td')).map(td => {
                // For cells with badges, get the text content
                if (td.querySelector('.badge')) {
                    return '"' + td.querySelector('.badge').textContent.trim() + '"';
                }
                // For other cells, just get the text
                return '"' + td.textContent.trim() + '"';
            });
            
            // Exclude the Actions column
            csv.push(cells.slice(0, -1).join(','));
        });
        
        // Create a CSV file and force download
        const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'exam_results.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
</script>

<?php require_once __DIR__ . '/includes/footer_fixed.php'; ?>
