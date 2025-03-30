<?php
// Simple script to return question data as JSON
// No output buffering or complex logic - just direct JSON output

// Set headers first, before any output
header('Content-Type: application/json; charset=utf-8');

// Load configuration and models
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Answer.php';

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check for question ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['message'] = 'Question ID is required';
    echo json_encode($response);
    exit;
}

try {
    // Get question ID
    $questionId = (int)$_GET['id'];
    
    // Initialize models
    $questionModel = new Question();
    $answerModel = new Answer();
    
    // Get question data
    $question = $questionModel->getById($questionId);
    
    if (!$question) {
        $response['message'] = 'Question not found';
        echo json_encode($response);
        exit;
    }
    
    // Get answers
    $answers = $answerModel->getAnswersByQuestionId($questionId);
    
    // Set success response
    $response = [
        'success' => true,
        'question' => $question,
        'answers' => $answers
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Output JSON response
echo json_encode($response);
exit; 