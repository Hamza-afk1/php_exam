<?php
// Start output buffering to prevent any unexpected output
ob_start();

// Turn off display errors to prevent them from corrupting JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Answer.php';

// Function to return JSON error response
function returnError($message, $statusCode = 400) {
    // Clean any output buffer
    if (ob_get_length()) ob_clean();
    
    // Set headers
    header('Content-Type: application/json');
    http_response_code($statusCode);
    
    // Return error
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

try {
    // Start the session
    Session::init();
    
    // Check login status
    if (!Session::isLoggedIn()) {
        returnError('User not logged in', 401);
    }

    // Check if user is formateur
    if (Session::get('role') !== 'formateur') {
        returnError('Access denied', 403);
    }

    // Get the formateur ID from session
    $formateurId = Session::get('user_id');

    // Initialize models
    $userModel = new User();
    $examModel = new Exam();
    $questionModel = new Question();
    $answerModel = new Answer();

    // Validate request
    if (!isset($_GET['question_id']) || empty($_GET['question_id'])) {
        returnError('Question ID is required');
    }

    // Cast to integer for security
    $questionId = (int)$_GET['question_id'];
    
    // Log the request
    error_log("Loading question data for ID: " . $questionId);

    // Get the question
    $question = $questionModel->getById($questionId);

    if (!$question) {
        returnError('Question not found', 404);
    }

    // Verify access rights - check if the question belongs to an exam owned by this formateur
    $exam = $examModel->getById($question['exam_id']);
    if (!$exam || $exam['formateur_id'] != $formateurId) {
        returnError('You do not have permission to access this question', 403);
    }

    // Get answers for this question
    $answers = $answerModel->getAnswersByQuestionId($questionId);

    // Clear the output buffer
    if (ob_get_length()) ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Prepare and send response
    $responseData = [
        'success' => true,
        'question' => $question,
        'answers' => $answers
    ];
    
    echo json_encode($responseData);
    
} catch (Exception $e) {
    error_log("Error in update_questions.php: " . $e->getMessage());
    returnError('An error occurred: ' . $e->getMessage(), 500);
}

// End output buffer
if (ob_get_length()) ob_end_flush();
?>
