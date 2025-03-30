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
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Result.php';
require_once __DIR__ . '/../models/Answer.php';

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
$questionModel = new Question();
$resultModel = new Result();
$answerModel = new Answer();

// Get the exam ID from URL
$examId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid exam ID, redirect to dashboard
if ($examId <= 0) {
    header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
    exit;
}

// Get exam details
$exam = $examModel->getById($examId);
if (!$exam) {
    header('Location: ' . BASE_URL . '/stagiaire/dashboard.php');
    exit;
}

// Check if user has already taken this exam
$existingResult = $resultModel->getResultByStagiaireAndExam($stagiaireId, $examId);
if ($existingResult) {
    // If exam has been taken, redirect to exam complete page with already_taken parameter
    header('Location: ' . BASE_URL . '/stagiaire/exam_complete.php?exam_id=' . $examId . '&already_taken=1');
    exit;
}

// Get all questions for this exam
$questions = $questionModel->getQuestionsByExamId($examId);

// Randomize questions for anti-cheating (keeping the index for numbering)
$questionOrder = range(0, count($questions) - 1);
shuffle($questionOrder);
$questionsRandomized = [];
foreach ($questionOrder as $index) {
    $questionsRandomized[] = $questions[$index];
}
$questions = $questionsRandomized;

// Process form submission (when answers are submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    // Debug
    error_log('Processing exam submission for exam ID: ' . $examId . ', student ID: ' . $stagiaireId);
    
    // Collect the answers
    $userAnswers = isset($_POST['answers']) ? $_POST['answers'] : [];
    $totalScore = 0;
    $maxScore = 0;
    $answeredQuestions = 0;
    
    // Debug
    error_log('Number of answers received: ' . count($userAnswers));
    
    // Process each question and calculate score
    foreach ($questions as $question) {
        $questionId = $question['id'];
        $questionType = $question['question_type'];
        $questionPoints = isset($question['points']) ? (int)$question['points'] : 1;
        $maxScore += $questionPoints;
        
        // Check if question was answered
        if (isset($userAnswers[$questionId])) {
            $answeredQuestions++;
            $userAnswer = $userAnswers[$questionId];
            $isCorrect = false;
            
            // Process different question types
            if ($questionType === 'qcm') {
                // Multiple choice questions
                $correctAnswers = null;
                if (is_string($question['correct_answer'])) {
                    $correctAnswers = json_decode($question['correct_answer'], true);
                } else if (is_array($question['correct_answer'])) {
                    $correctAnswers = $question['correct_answer'];
                } else {
                    error_log('Warning: correct_answer is neither string nor array. Type: ' . gettype($question['correct_answer']));
                    $correctAnswers = [];
                }
                
                // Ensure $correctAnswers is an array
                if (!is_array($correctAnswers)) {
                    $correctAnswers = [];
                }
                
                if (is_array($userAnswer) && is_array($correctAnswers)) {
                    // Convert to simple arrays for comparison
                    sort($userAnswer);
                    sort($correctAnswers);
                    $isCorrect = ($userAnswer == $correctAnswers);
                }
            } else if ($questionType === 'true_false') {
                // True/False questions
                $isCorrect = ($userAnswer == $question['correct_answer']);
            } else {
                // Open-ended questions - will need manual grading
                // We'll store the answer but not mark it correct or incorrect
                $isCorrect = null;
            }
            
            // Add to score if correct
            if ($isCorrect === true) {
                $totalScore += $questionPoints;
            }
            
            // Save the answer
            $answerData = [
                'exam_id' => $examId,
                'question_id' => $questionId,
                'stagiaire_id' => $stagiaireId,
                'answer_text' => is_array($userAnswer) ? json_encode($userAnswer) : $userAnswer,
                'is_correct' => $isCorrect === null ? null : ($isCorrect ? 1 : 0)
            ];
            $answerModel->create($answerData);
        }
    }
    
    // Calculate percentage score
    $percentageScore = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
    $percentageScore = round($percentageScore, 2);
    
    // Check if passing score is reached
    $passingScore = $exam['passing_score'] ?? 70;
    $status = $percentageScore >= $passingScore ? 'passed' : 'failed';
    
    // Determine if grading is needed (for open-ended questions)
    $needsGrading = false;
    foreach ($questions as $question) {
        if ($question['question_type'] === 'open') {
            $needsGrading = true;
            break;
        }
    }
    
    // Create the result record
    $resultData = [
        'exam_id' => $examId,
        'stagiaire_id' => $stagiaireId,
        'score' => $percentageScore,
        'total_score' => $maxScore,
        'graded_by' => null
    ];
    
    // Debug
    error_log('Attempting to save exam result: ' . json_encode($resultData));
    
    if ($resultModel->create($resultData)) {
        error_log('Exam result saved successfully. Redirecting to thank you page.');
        header('Location: ' . BASE_URL . '/stagiaire/thank_you.php?exam_id=' . $examId);
        exit;
    } else {
        error_log('Failed to save exam result. Database operation failed.');
        $error = "Failed to save exam results. Please try again or contact support.";
    }
}

// Set the page title
$pageTitle = "Taking Exam: " . htmlspecialchars($exam['name']);

// Disable header and footer for full-screen exam experience
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 60px;
        }
        .exam-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #343a40;
            color: white;
            padding: 10px 0;
            z-index: 1000;
        }
        .exam-timer {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border-radius: 4px;
            position: sticky;
            top: 60px;
            z-index: 999;
        }
        .exam-progress {
            margin-bottom: 0;
            height: 6px;
        }
        .warning-message {
            position: fixed;
            top: 70px;
            right: 20px;
            background-color: rgba(255, 193, 7, 0.8);
            color: #212529;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1100;
            display: none;
        }
        .question-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #007bff;
        }
        .question-card.active {
            border-left-color: #28a745;
        }
        .question-card.not-answered {
            border-left-color: #dc3545;
        }
        .question-text {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        .question-navigation {
            position: sticky;
            bottom: 20px;
            right: 20px;
            background-color: #343a40;
            color: white;
            border-radius: 8px;
            padding: 10px;
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .question-nav-btn {
            width: 36px;
            height: 36px;
            margin: 3px;
            padding: 0;
            font-size: 0.9rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .question-nav-btn.not-answered {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .question-nav-btn.active {
            background-color: #28a745;
            border-color: #28a745;
        }
        .submit-area {
            position: sticky;
            bottom: 0;
            background-color: white;
            padding: 15px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-top: 30px;
        }
        .fullscreen-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        /* Prevent text selection */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        /* Progress bar styling */
        .progress-container {
            padding: 0 20px;
        }
        .answer-options label {
            display: block;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .answer-options label:hover {
            background-color: #e9ecef;
        }
        .form-check-input:checked + label {
            background-color: #cfe2ff;
            border-color: #b6d4fe;
        }
        /* Submission overlay */
        .submission-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .submission-loader {
            background-color: white;
            padding: 30px 50px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .submission-loader i {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        .submission-loader p {
            font-size: 1.2rem;
            margin: 0;
        }
        #fallback-submit-container {
            margin-top: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Anti-cheating warning -->
    <div class="warning-message" id="warning-message">
        <i class="fas fa-exclamation-circle mr-2"></i> Cheating attempt detected! This incident has been logged.
    </div>

    <!-- Exam header with progress and timer -->
    <div class="exam-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0"><?php echo htmlspecialchars($exam['name']); ?></h5>
                </div>
                <div class="col-md-4 text-right">
                    <span id="timer-display">
                        <i class="fas fa-clock mr-1"></i> <span id="timer">00:00:00</span>
                    </span>
                </div>
            </div>
            <div class="progress-container mt-2">
                <div class="progress exam-progress">
                    <div class="progress-bar" id="exam-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Exam instructions and info -->
        <div class="alert alert-info mb-4">
            <h5><i class="fas fa-info-circle mr-2"></i> Exam Instructions</h5>
            <ul class="mb-0">
                <li>This exam has <?php echo count($questions); ?> questions worth a total of <?php echo array_sum(array_column($questions, 'points')); ?> points.</li>
                <li>Time limit: <?php echo $exam['time_limit']; ?> minutes. The exam will auto-submit when time expires.</li>
                <li>Passing score: <?php echo $exam['passing_score']; ?>%</li>
                <li>Do not refresh the page or navigate away from this exam.</li>
                <li>Anti-cheating measures are in place and all activities are logged.</li>
                <li>Submit your exam when you've completed all questions.</li>
            </ul>
        </div>

        <!-- Exam form -->
        <?php if (empty($questions)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> This exam has no questions. Please contact your instructor.
            </div>
        <?php else: ?>
            <form method="post" action="" id="examForm" autocomplete="off">
                <input type="hidden" name="submit_exam" value="1">
                <div id="question-container">
                    <?php foreach($questions as $index => $question): ?>
                        <div class="question-card mb-4 not-answered" id="question-card-<?php echo $index; ?>" data-question-id="<?php echo $question['id']; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Question <?php echo $index + 1; ?></h5>
                                <span class="badge badge-primary"><?php echo $question['points'] ?? 1; ?> points</span>
                            </div>
                            <p class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                            
                            <?php if ($question['question_type'] === 'qcm'): ?>
                                <!-- Multiple Choice Question -->
                                <?php 
                                $answers = $answerModel->getAnswersByQuestionId($question['id']);
                                
                                // Randomize answer order for anti-cheating
                                if (!empty($answers)) {
                                    shuffle($answers);
                                }
                                
                                if (!empty($answers)):
                                ?>
                                    <div class="form-group">
                                        <div class="answer-options">
                                            <?php foreach($answers as $answerIndex => $answer): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input question-input" type="checkbox" 
                                                        name="answers[<?php echo $question['id']; ?>][]" 
                                                        value="<?php echo $answer['id']; ?>"
                                                        id="answer-<?php echo $question['id']; ?>-<?php echo $answer['id']; ?>"
                                                        onchange="updateQuestionStatus(<?php echo $index; ?>)">
                                                    <label class="form-check-label" for="answer-<?php echo $question['id']; ?>-<?php echo $answer['id']; ?>">
                                                        <?php echo htmlspecialchars($answer['answer_text']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Select all that apply.
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">No answer options available for this question.</div>
                                <?php endif; ?>
                            
                            <?php elseif ($question['question_type'] === 'true_false'): ?>
                                <!-- True/False Question -->
                                <div class="form-group">
                                    <div class="answer-options">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input question-input" type="radio" 
                                                name="answers[<?php echo $question['id']; ?>]" 
                                                value="True"
                                                id="answer-<?php echo $question['id']; ?>-true"
                                                onchange="updateQuestionStatus(<?php echo $index; ?>)">
                                            <label class="form-check-label" for="answer-<?php echo $question['id']; ?>-true">
                                                True
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input question-input" type="radio" 
                                                name="answers[<?php echo $question['id']; ?>]" 
                                                value="False"
                                                id="answer-<?php echo $question['id']; ?>-false"
                                                onchange="updateQuestionStatus(<?php echo $index; ?>)">
                                            <label class="form-check-label" for="answer-<?php echo $question['id']; ?>-false">
                                                False
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            
                            <?php else: ?>
                                <!-- Open Ended Question -->
                                <div class="form-group">
                                    <textarea class="form-control question-input" 
                                        name="answers[<?php echo $question['id']; ?>]"
                                        id="answer-<?php echo $question['id']; ?>"
                                        rows="4"
                                        placeholder="Enter your answer here..."
                                        onpaste="return false;"
                                        onchange="updateQuestionStatus(<?php echo $index; ?>)"
                                        onkeyup="updateQuestionStatus(<?php echo $index; ?>)"></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Your answer will be reviewed by an instructor.
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Question navigation -->
                <div class="question-navigation">
                    <div class="d-flex justify-content-center flex-wrap mb-2">
                        <?php for($i = 0; $i < count($questions); $i++): ?>
                            <button type="button" class="btn btn-primary question-nav-btn not-answered" id="nav-btn-<?php echo $i; ?>" onclick="scrollToQuestion(<?php echo $i; ?>)">
                                <?php echo $i + 1; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <div class="text-center text-white">
                        <small>
                            <span class="badge badge-success">Answered</span>
                            <span class="badge badge-danger">Not Answered</span>
                        </small>
                    </div>
                </div>
                
                <!-- Submit area -->
                <div class="submit-area mt-4">
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Progress: <span id="answered-count">0</span>/<span id="total-questions"><?php echo count($questions); ?></span> questions answered</span>
                            <button type="button" id="check-answers-btn" class="btn btn-info btn-sm" onclick="checkAnswers()">
                                <i class="fas fa-check-circle mr-1"></i> Check Answers
                            </button>
                        </div>
                    </div>
                    <button type="button" name="prepare_submit" id="submit-exam-btn" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane mr-1"></i> Submit Exam
                    </button>
                    <div id="emergency-submit" style="display:none; margin-top:15px;" class="alert alert-warning">
                        <p><strong>Emergency submission option:</strong> If you're having trouble submitting your exam using the button above, click this button:</p>
                        <button type="button" class="btn btn-danger" onclick="directSubmitExam()">Emergency Submit</button>
                    </div>
                    <small class="d-block mt-2 text-muted">
                        You cannot change your answers after submission.
                    </small>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Fullscreen button -->
    <button type="button" id="fullscreen-btn" class="btn btn-secondary fullscreen-btn" onclick="toggleFullScreen()">
        <i class="fas fa-expand" id="fullscreen-icon"></i>
    </button>

    <!-- Confirmation modal -->
    <div class="modal fade" id="submitConfirmModal" tabindex="-1" role="dialog" aria-labelledby="submitConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="submitConfirmModalLabel">Confirm Submission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit your exam?</p>
                    <div id="unanswered-warning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle mr-2"></i> You have <span id="unanswered-count">0</span> unanswered questions.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Go Back to Exam</button>
                    <button type="button" class="btn btn-primary" id="final-submit-btn">Yes, Submit My Exam</button>
                    <div id="fallback-submit-container">
                        <hr>
                        <p class="text-danger"><strong>If the standard submission doesn't work, try this:</strong></p>
                        <button type="button" class="btn btn-warning" onclick="directSubmitExam()">Alternative Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Anti-cheating variables
        let focusLost = 0;
        let examStarted = false;
        let warningShown = false;
        let tabSwitchAttempts = 0;
        
        // Store timestamps for analytics
        let examAnalytics = {
            startTime: new Date().getTime(),
            endTime: null,
            focusEvents: [],
            questionTimeSpent: {}
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Set up timer
            let timeLimit = <?php echo $exam['time_limit']; ?> * 60; // convert to seconds
            startTimer(timeLimit);
            
            // Initialize exam
            examStarted = true;
            updateAnsweredCount();
            requestFullscreen();

            // Record time spent on each question
            initializeQuestionTimeTracking();
            
            // Show emergency submit after 3 seconds (for users who have submission issues)
            setTimeout(function() {
                document.getElementById('emergency-submit').style.display = 'block';
            }, 3000);
            
            // Anti-cheat: Disable right click
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showWarning("Right-clicking is disabled during the exam");
                return false;
            });
            
            // Anti-cheat: Detect tab/window switching
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'hidden') {
                    focusLost++;
                    tabSwitchAttempts++;
                    examAnalytics.focusEvents.push({
                        time: new Date().getTime(),
                        type: 'lost'
                    });
                    if (tabSwitchAttempts > 2) {
                        showWarning("Tab switching detected! Your actions are being logged.");
                    }
                } else {
                    examAnalytics.focusEvents.push({
                        time: new Date().getTime(),
                        type: 'regained'
                    });
                }
            });
            
            // Anti-cheat: Detect and disable keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Disable Ctrl+C, Ctrl+V, Ctrl+P, F12, Alt+Tab
                if (
                    (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'p')) ||
                    (e.key === 'F12' || e.key === 'PrintScreen')
                ) {
                    e.preventDefault();
                    showWarning("Keyboard shortcuts are disabled during the exam");
                    return false;
                }
            });
            
            // Handle form submission with confirmation
            document.getElementById('submit-exam-btn').addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check for unanswered questions
                const totalQuestions = <?php echo count($questions); ?>;
                const answeredQuestions = document.querySelectorAll('.question-card.active').length;
                const unansweredCount = totalQuestions - answeredQuestions;
                
                // Show modal with warning if needed
                if (unansweredCount > 0) {
                    document.getElementById('unanswered-count').textContent = unansweredCount;
                    document.getElementById('unanswered-warning').classList.remove('d-none');
                } else {
                    document.getElementById('unanswered-warning').classList.add('d-none');
                }
                
                // Show confirmation modal
                $('#submitConfirmModal').modal('show');
            });
            
            // Final submit button
            document.getElementById('final-submit-btn').addEventListener('click', function() {
                try {
                    // Close the modal immediately
                    $('#submitConfirmModal').modal('hide');
                    
                    // Add a delay before showing the loading overlay and submitting
                    setTimeout(function() {
                        // Show the loading overlay
                        showSubmissionLoader();
                        
                        // Then submit with another small delay
                        setTimeout(function() {
                            examAnalytics.endTime = new Date().getTime();
                            console.log('Submitting form...');
                            document.getElementById('examForm').submit();
                            console.log('Form submitted');
                        }, 300);
                    }, 500);
                } catch (e) {
                    console.error('Error submitting form:', e);
                    alert('There was an error submitting your exam. Please try the alternative submission button below.');
                    // Show fallback submission button
                    document.getElementById('fallback-submit-container').style.display = 'block';
                }
            });
            
            // Auto-save answers periodically
            setInterval(saveAnswers, 30000); // Every 30 seconds
        });
        
        // Start the countdown timer
        function startTimer(seconds) {
            const timerElement = document.getElementById('timer');
            let remainingSeconds = seconds;
            
            const timerInterval = setInterval(function() {
                remainingSeconds--;
                
                // Format the time
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const secs = remainingSeconds % 60;
                
                timerElement.textContent = 
                    (hours > 0 ? (hours < 10 ? '0' : '') + hours + ':' : '') + 
                    (minutes < 10 ? '0' : '') + minutes + ':' + 
                    (secs < 10 ? '0' : '') + secs;
                
                // Visual warning when time is running out
                if (remainingSeconds <= 300) { // 5 minutes left
                    timerElement.parentElement.classList.add('text-danger');
                    if (remainingSeconds <= 60) { // 1 minute left
                        timerElement.parentElement.classList.add('font-weight-bold');
                        if (!warningShown) {
                            alert('Warning: Only 1 minute left in your exam!');
                            warningShown = true;
                        }
                    }
                }
                
                // When time is up
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = "Time's Up!";
                    alert('Time is up! Your exam will be submitted automatically.');
                    examAnalytics.endTime = new Date().getTime();
                    document.getElementById('examForm').submit();
                }
            }, 1000);
        }
        
        // Update the status of a question (answered/not answered)
        function updateQuestionStatus(index) {
            const questionCard = document.getElementById('question-card-' + index);
            const navButton = document.getElementById('nav-btn-' + index);
            const questionId = questionCard.getAttribute('data-question-id');
            let answered = false;
            
            // Check if the question is answered based on its type
            const inputs = questionCard.querySelectorAll('.question-input');
            inputs.forEach(input => {
                if ((input.type === 'checkbox' || input.type === 'radio') && input.checked) {
                    answered = true;
                } else if (input.type === 'textarea' && input.value.trim() !== '') {
                    answered = true;
                }
            });
            
            // Update UI classes
            if (answered) {
                questionCard.classList.remove('not-answered');
                questionCard.classList.add('active');
                navButton.classList.remove('not-answered');
                navButton.classList.add('active');
            } else {
                questionCard.classList.remove('active');
                questionCard.classList.add('not-answered');
                navButton.classList.remove('active');
                navButton.classList.add('not-answered');
            }
            
            // Update progress indicators
            updateAnsweredCount();
        }
        
        // Update the count of answered questions
        function updateAnsweredCount() {
            const totalQuestions = <?php echo count($questions); ?>;
            const answeredQuestions = document.querySelectorAll('.question-card.active').length;
            
            document.getElementById('answered-count').textContent = answeredQuestions;
            
            // Update progress bar
            const progressPercent = (answeredQuestions / totalQuestions) * 100;
            document.getElementById('exam-progress-bar').style.width = progressPercent + '%';
            document.getElementById('exam-progress-bar').setAttribute('aria-valuenow', progressPercent);
            
            return answeredQuestions;
        }
        
        // Scroll to a specific question
        function scrollToQuestion(index) {
            const questionCard = document.getElementById('question-card-' + index);
            questionCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Show warning message
        function showWarning(message) {
            const warningElement = document.getElementById('warning-message');
            warningElement.textContent = message;
            warningElement.style.display = 'block';
            
            setTimeout(function() {
                warningElement.style.display = 'none';
            }, 5000);
        }
        
        // Toggle fullscreen mode
        function toggleFullScreen() {
            const icon = document.getElementById('fullscreen-icon');
            
            if (!document.fullscreenElement) {
                requestFullscreen();
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            }
        }
        
        // Request fullscreen
        function requestFullscreen() {
            const elem = document.documentElement;
            
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.mozRequestFullScreen) { /* Firefox */
                elem.mozRequestFullScreen();
            } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE/Edge */
                elem.msRequestFullscreen();
            }
        }
        
        // Check for unanswered questions
        function checkAnswers() {
            const totalQuestions = <?php echo count($questions); ?>;
            const answeredQuestions = updateAnsweredCount();
            const unansweredCount = totalQuestions - answeredQuestions;
            
            if (unansweredCount === 0) {
                alert('All questions have been answered! You can now submit your exam.');
            } else {
                alert('You have ' + unansweredCount + ' unanswered question(s). Please review your answers before submitting.');
                
                // Find the first unanswered question and scroll to it
                const unansweredCards = document.querySelectorAll('.question-card.not-answered');
                if (unansweredCards.length > 0) {
                    unansweredCards[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
        
        // Save answers to localStorage as backup
        function saveAnswers() {
            const formData = new FormData(document.getElementById('examForm'));
            const answersData = {};
            
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('answers')) {
                    if (answersData[key]) {
                        if (!Array.isArray(answersData[key])) {
                            answersData[key] = [answersData[key]];
                        }
                        answersData[key].push(value);
                    } else {
                        answersData[key] = value;
                    }
                }
            }
            
            try {
                localStorage.setItem('exam_<?php echo $examId; ?>_answers', JSON.stringify(answersData));
                console.log('Answers auto-saved');
            } catch (e) {
                console.error('Failed to auto-save answers:', e);
            }
        }
        
        // Initialize question time tracking
        function initializeQuestionTimeTracking() {
            const questions = document.querySelectorAll('.question-card');
            questions.forEach((question, index) => {
                examAnalytics.questionTimeSpent[index] = 0;
            });
            
            // Use Intersection Observer to track visible questions
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const questionIndex = parseInt(entry.target.id.replace('question-card-', ''));
                    if (entry.isIntersecting) {
                        // Question came into view, start tracking time
                        entry.target.dataset.viewStartTime = new Date().getTime();
                    } else if (entry.target.dataset.viewStartTime) {
                        // Question went out of view, add to time spent
                        const timeSpent = new Date().getTime() - parseInt(entry.target.dataset.viewStartTime);
                        examAnalytics.questionTimeSpent[questionIndex] += timeSpent / 1000; // in seconds
                        delete entry.target.dataset.viewStartTime;
                    }
                });
            }, { threshold: 0.5 });
            
            // Observe all questions
            questions.forEach(question => {
                observer.observe(question);
            });
        }
        
        // Disable copy/paste for textarea fields
        document.addEventListener('copy', function(e) {
            if (examStarted) {
                e.preventDefault();
                showWarning("Copying is disabled during the exam");
                return false;
            }
        });
        
        document.addEventListener('paste', function(e) {
            if (examStarted) {
                e.preventDefault();
                showWarning("Pasting is disabled during the exam");
                return false;
            }
        });
        
        // Confirm before leaving the page
        window.addEventListener('beforeunload', function(e) {
            if (examStarted) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Don't show warning when submitting the form
        document.getElementById('examForm').addEventListener('submit', function() {
            examStarted = false;
            window.removeEventListener('beforeunload', function() {});
        });

        // Add a direct submit option as fallback
        function directSubmitExam() {
            showSubmissionLoader();
            setTimeout(function() {
                document.getElementById('examForm').submit();
            }, 100);
            return true;
        }
        
        // Show a submission in progress indicator
        function showSubmissionLoader() {
            // Create and show a loading overlay
            const overlay = document.createElement('div');
            overlay.className = 'submission-overlay';
            overlay.innerHTML = `
                <div class="submission-loader">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    <p>Submitting your exam...</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
    </script>
</body>
</html> 