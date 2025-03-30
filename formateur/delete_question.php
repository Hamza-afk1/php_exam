<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug logging
error_log("Delete question script started");
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('GET data: ' . print_r($_GET, true));
error_log('POST data: ' . print_r($_POST, true));

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Answer.php';

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
$questionModel = new Question();
$answerModel = new Answer();

// Process delete request
$message = '';
$error = '';

// Process both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get question ID and exam ID from either POST or GET
    $questionId = 0;
    $examId = 0;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $questionId = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
        $examId = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
    } else {
        $questionId = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;
        $examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
    }
    
    error_log("Processing deletion - Question ID: $questionId, Exam ID: $examId");
    
    if ($questionId && $examId) {
        $question = $questionModel->getById($questionId);
        
        // Check if the question exists and belongs to an exam owned by this formateur
        if ($question) {
            $exam = $examModel->getById($question['exam_id']);
            if ($exam && $exam['formateur_id'] == $formateurId) {
                // Delete the question and its answers
                if ($questionModel->delete($questionId)) {
                    $message = "Question deleted successfully!";
                } else {
                    $error = "Failed to delete question!";
                }
            } else {
                $error = "You don't have permission to delete this question.";
            }
        } else {
            $error = "Question not found!";
        }
    } else {
        $error = "Invalid question or exam ID!";
    }
}

// Redirect back to questions page
if (!empty($message)) {
    header('Location: ' . BASE_URL . '/formateur/questions.php?exam_id=' . $examId . '&message=' . urlencode($message));
} else if (!empty($error)) {
    header('Location: ' . BASE_URL . '/formateur/questions.php?exam_id=' . $examId . '&error=' . urlencode($error));
} else {
    header('Location: ' . BASE_URL . '/formateur/questions.php?exam_id=' . $examId);
}
exit;
?> 