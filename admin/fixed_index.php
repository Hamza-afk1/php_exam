<?php
// Include header with error handling
require_once __DIR__ . '/includes/fix_header.php';

// Safely initialize models 
try {
    require_once __DIR__ . '/../models/User.php';
    require_once __DIR__ . '/../models/Exam.php';
    require_once __DIR__ . '/../models/Result.php';

    // Get statistics
    $userModel = new User();
    $examModel = new Exam();
    $resultModel = new Result();

    $formateurCount = count($userModel->getUsersByRole('formateur'));
    $stagiaireCount = count($userModel->getUsersByRole('stagiaire'));
    $examCount = count($examModel->getAll());

    // Get recent results
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
        // Just continue with empty results
    }
} catch (Exception $e) {
    // Set default values if there's an error
    $formateurCount = 0;
    $stagiaireCount = 0;
    $examCount = 0;
    $recentResults = [];
    
    echo '<div class="alert alert-warning">There was an error loading some data: ' . $e->getMessage() . '</div>';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <a href="users.php?action=add" class="btn btn-sm btn-outline-secondary">Add User</a>
            <a href="exams.php" class="btn btn-sm btn-outline-secondary">Manage Exams</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Formateurs</h6>
                        <h2 class="mb-0"><?php echo $formateurCount; ?></h2>
                    </div>
                    <i class="fas fa-chalkboard-teacher fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="users.php?role=formateur" class="text-white">View Formateurs</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Stagiaires</h6>
                        <h2 class="mb-0"><?php echo $stagiaireCount; ?></h2>
                    </div>
                    <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="users.php?role=stagiaire" class="text-white">View Stagiaires</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Exams</h6>
                        <h2 class="mb-0"><?php echo $examCount; ?></h2>
                    </div>
                    <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="exams.php" class="text-white">View Exams</a>
            </div>
        </div>
    </div>
</div>

<h2>Recent Results</h2>
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Stagiaire</th>
                <th>Exam</th>
                <th>Score</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentResults)): ?>
                <tr>
                    <td colspan="6" class="text-center">No results found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentResults as $result): ?>
                <tr>
                    <td><?php echo $result['id']; ?></td>
                    <td><?php echo htmlspecialchars($result['stagiaire_name']); ?></td>
                    <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                    <td><?php echo $result['score']; ?>%</td>
                    <td><?php echo date('F j, Y', strtotime($result['created_at'])); ?></td>
                    <td>
                        <a href="results.php?action=view&id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// Include footer - using a simple footer instead of including another file that might have issues
?>
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

