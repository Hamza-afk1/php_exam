<?php
// Define the file to be updated
$questions_file = __DIR__ . '/questions.php';

// Read the current file content
$content = file_get_contents($questions_file);

// Update: Initialize questionData with points field
$search1 = '$questionData = [
        \'question_type\' => \'qcm\',
        \'question_text\' => \'\',
        \'options\' => [\'\', \'\', \'\', \'\'],
        \'correct_answer\' => []
    ];';
$replace1 = '$questionData = [
        \'question_type\' => \'qcm\',
        \'question_text\' => \'\',
        \'options\' => [\'\', \'\', \'\', \'\'],
        \'correct_answer\' => [],
        \'points\' => 1
    ];';
$content = str_replace($search1, $replace1, $content);

// Update: Add points to form submission handler
$search2 = '$questionData = [
            \'exam_id\' => $examId,
            \'question_type\' => $_POST[\'question_type\'],
            \'question_text\' => $_POST[\'question_text\']
        ];';
$replace2 = '$questionData = [
            \'exam_id\' => $examId,
            \'question_type\' => $_POST[\'question_type\'],
            \'question_text\' => $_POST[\'question_text\'],
            \'points\' => (int)$_POST[\'points\'] ?: 1
        ];';
$content = str_replace($search2, $replace2, $content);

// Update: Add points field to question form
$search3 = '<div class="form-group">
                                    <label for="question_text">Question Text</label>
                                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo $action === \'edit\' ? htmlspecialchars($questionData[\'question_text\']) : \'\'; ?></textarea>
                                </div>';
$replace3 = '<div class="form-group">
                                    <label for="question_text">Question Text</label>
                                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo $action === \'edit\' ? htmlspecialchars($questionData[\'question_text\']) : \'\'; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="points">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" min="1" required 
                                           value="<?php echo $action === \'edit\' ? htmlspecialchars($questionData[\'points\']) : \'1\'; ?>">
                                    <small class="form-text text-muted">
                                        Points to award for this question. For QCM questions, this will be automatically assigned based on the total points for the exam.
                                        <?php if ($action === \'add\' && $_GET[\'question_type\'] === \'qcm\'): ?>
                                            <?php 
                                            // Calculate default points for new QCM questions
                                            $defaultPoints = $questionModel->calculateDefaultPoints($examId);
                                            echo "Recommended points: " . $defaultPoints;
                                            ?>
                                        <?php endif; ?>
                                    </small>
                                </div>';
$content = str_replace($search3, $replace3, $content);

// Update: Add total points to the exam info display
$search4 = 'echo \'<p>Time Limit: \' . htmlspecialchars($exam[\'time_limit\']) . \' minutes</p>\';
                echo \'<p>Passing Score: \' . htmlspecialchars($exam[\'passing_score\']) . \'%</p>\';';
$replace4 = 'echo \'<p>Time Limit: \' . htmlspecialchars($exam[\'time_limit\']) . \' minutes</p>\';
                echo \'<p>Passing Score: \' . htmlspecialchars($exam[\'passing_score\']) . \'%</p>\';
                echo \'<p>Total Points: \' . htmlspecialchars($exam[\'total_points\']) . \'</p>\';';
$content = str_replace($search4, $replace4, $content);

// Update: Add points column to the questions table
$search5 = '                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Type</th>
                                                <th>Question</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>';
$replace5 = '                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Type</th>
                                                <th>Question</th>
                                                <th>Points</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>';
$content = str_replace($search5, $replace5, $content);

// Update: Add points column data to the questions table
$search6 = '                                                    <td><?php echo htmlspecialchars($question[\'question_text\']); ?></td>
                                                    <td>';
$replace6 = '                                                    <td><?php echo htmlspecialchars($question[\'question_text\']); ?></td>
                                                    <td><?php echo htmlspecialchars($question[\'points\']); ?></td>
                                                    <td>';
$content = str_replace($search6, $replace6, $content);

// Write the updated content back to the file
file_put_contents($questions_file, $content);

echo "Questions.php has been updated successfully!";
?>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>

