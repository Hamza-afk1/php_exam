<?php
require_once 'Model.php';

class Answer extends Model {
    protected $table = 'answers';
    
    public function create(array $data) {
        $query = "INSERT INTO answers (exam_id, stagiaire_id, question_id, answer_text, is_correct, graded_points, max_points) 
                  VALUES (:exam_id, :stagiaire_id, :question_id, :answer_text, :is_correct, :graded_points, :max_points)";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':exam_id' => $data['exam_id'],
            ':stagiaire_id' => $data['stagiaire_id'],
            ':question_id' => $data['question_id'],
            ':answer_text' => $data['answer_text'],
            ':is_correct' => $data['is_correct'],
            ':graded_points' => isset($data['graded_points']) ? $data['graded_points'] : null,
            ':max_points' => isset($data['max_points']) ? $data['max_points'] : 1
        ];
        
        if ($this->db->execute($stmt, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update(array $data, $id) {
        $query = "UPDATE answers SET 
                  answer_text = :answer_text, 
                  is_correct = :is_correct,
                  graded_points = :graded_points
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':answer_text' => isset($data['answer_text']) ? $data['answer_text'] : null,
            ':is_correct' => isset($data['is_correct']) ? $data['is_correct'] : null,
            ':graded_points' => isset($data['graded_points']) ? $data['graded_points'] : null,
            ':id' => $id
        ];
        
        return $this->db->execute($stmt, $params);
    }
    
    public function getAnswersByExamAndStagiaire($examId, $stagiaireId) {
        $query = "SELECT a.*, q.question_text, q.question_type, q.options, q.correct_answer, q.points 
                  FROM answers a
                  JOIN questions q ON a.question_id = q.id
                  WHERE a.exam_id = :exam_id AND a.stagiaire_id = :stagiaire_id";
        $stmt = $this->db->prepare($query);
        $params = [
            ':exam_id' => $examId,
            ':stagiaire_id' => $stagiaireId
        ];
        
        $this->db->execute($stmt, $params);
        
        $answers = $this->db->resultSet($stmt);
        
        // Process each answer to handle JSON fields
        foreach ($answers as &$answer) {
            // Convert JSON options string to PHP array
            if (!empty($answer['options'])) {
                $answer['options'] = json_decode($answer['options'], true);
            }
            
            // Convert answer_text to array for QCM if it's a JSON string
            if ($answer['question_type'] === 'qcm' && !empty($answer['answer_text']) && 
                ($answer['answer_text'][0] === '[' || $answer['answer_text'][0] === '{')) {
                $answer['answer_text'] = json_decode($answer['answer_text'], true);
            }
            
            // Convert correct_answer to array for QCM if it's a JSON string
            if ($answer['question_type'] === 'qcm' && !empty($answer['correct_answer']) &&
                ($answer['correct_answer'][0] === '[' || $answer['correct_answer'][0] === '{')) {
                $answer['correct_answer'] = json_decode($answer['correct_answer'], true);
            }
        }
        
        return $answers;
    }
    
    public function getAnswersByExam($examId) {
        $query = "SELECT a.*, 
                  q.question_text, q.question_type, q.options, q.correct_answer, q.points,
                  u.username as stagiaire_name
                  FROM answers a
                  JOIN questions q ON a.question_id = q.id
                  JOIN users u ON a.stagiaire_id = u.id
                  WHERE a.exam_id = :exam_id
                  ORDER BY a.stagiaire_id, q.id";
        $stmt = $this->db->prepare($query);
        $params = [':exam_id' => $examId];
        
        $this->db->execute($stmt, $params);
        
        $answers = $this->db->resultSet($stmt);
        
        // Process each answer to handle JSON fields
        foreach ($answers as &$answer) {
            // Convert JSON options string to PHP array
            if (!empty($answer['options'])) {
                $answer['options'] = json_decode($answer['options'], true);
            }
            
            // Convert answer_text to array for QCM if it's a JSON string
            if ($answer['question_type'] === 'qcm' && !empty($answer['answer_text']) && 
                ($answer['answer_text'][0] === '[' || $answer['answer_text'][0] === '{')) {
                $answer['answer_text'] = json_decode($answer['answer_text'], true);
            }
            
            // Convert correct_answer to array for QCM if it's a JSON string
            if ($answer['question_type'] === 'qcm' && !empty($answer['correct_answer']) &&
                ($answer['correct_answer'][0] === '[' || $answer['correct_answer'][0] === '{')) {
                $answer['correct_answer'] = json_decode($answer['correct_answer'], true);
            }
        }
        
        return $answers;
    }
    
    public function gradeOpenAnswer($answerId, $points) {
        $query = "UPDATE answers SET graded_points = :points WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $params = [
            ':points' => $points,
            ':id' => $answerId
        ];
        
        return $this->db->execute($stmt, $params);
    }
    
    public function getUngraded() {
        $query = "SELECT a.*, 
                  q.question_text, q.question_type, q.options, q.correct_answer, q.points,
                  u.username as stagiaire_name
                  FROM answers a
                  JOIN questions q ON a.question_id = q.id
                  JOIN users u ON a.stagiaire_id = u.id
                  WHERE a.is_correct IS NULL";
        $stmt = $this->db->prepare($query);
        
        $this->db->execute($stmt);
        
        return $this->db->resultSet($stmt);
    }
    
    /**
     * Get ungraded answers for a specific exam
     *
     * @param int $examId The ID of the exam
     * @return array Ungraded answers with question and stagiaire details
     */
    public function getUngradedAnswersByExam($examId) {
        $query = "SELECT a.*, 
                  q.question_text, 
                  q.question_type, 
                  q.points,
                  u.username as stagiaire_name
                  FROM answers a
                  JOIN questions q ON a.question_id = q.id
                  JOIN users u ON a.stagiaire_id = u.id
                  WHERE a.exam_id = :exam_id 
                    AND q.question_type = 'open'
                    AND a.is_correct IS NULL
                  ORDER BY u.username, q.id";
                  
        $stmt = $this->db->prepare($query);
        $params = [
            ':exam_id' => $examId
        ];
        
        $this->db->execute($stmt, $params);
        
        return $this->db->resultSet($stmt);
    }
    
    /**
     * Calculate the total score for a student's exam
     * Includes automatic scoring for QCM questions and manual scoring for open-ended questions
     *
     * @param int $examId
     * @param int $stagiaireId
     * @return array Total score information
     */
    public function calculateTotalScore($examId, $stagiaireId) {
        // Get all answers for this exam and student
        $answers = $this->getAnswersByExamAndStagiaire($examId, $stagiaireId);
        
        // Initialize counters
        $totalPoints = 0;
        $earnedPoints = 0;
        $maxPoints = 0;
        $totalQcmPoints = 0;
        $earnedQcmPoints = 0;
        $pendingGrading = false;
        
        // Process each answer
        foreach ($answers as $answer) {
            $maxPoints += $answer['points'];
            
            if ($answer['question_type'] === 'qcm') {
                // For QCM questions, we can calculate points automatically
                $totalQcmPoints += $answer['points'];
                
                if ($answer['is_correct'] == 1) {
                    $earnedPoints += $answer['points'];
                    $earnedQcmPoints += $answer['points'];
                }
            } else {
                // For open-ended questions, check if they've been graded
                if ($answer['graded_points'] !== null) {
                    $earnedPoints += $answer['graded_points'];
                } else {
                    $pendingGrading = true;
                }
            }
        }
        
        // Get the exam to find total points
        $examModel = new Exam();
        $exam = $examModel->getById($examId);
        $examTotalPoints = $exam['total_points'];
        
        // Calculate percentage score
        $percentageScore = ($maxPoints > 0 && !$pendingGrading) ? round(($earnedPoints / $maxPoints) * 100) : null;
        
        // If all questions have been graded, update the result
        if (!$pendingGrading) {
            $resultModel = new Result();
            $resultData = [
                'score' => $percentageScore,
                'total_score' => $earnedPoints,
                'graded_by' => 1 // Assuming system grading for QCM
            ];
            $resultModel->updateResultByStagiaireAndExam($resultData, $stagiaireId, $examId);
        }
        
        // Return the score data
        return [
            'earned_points' => $earnedPoints,
            'max_points' => $maxPoints,
            'total_score' => $earnedPoints,
            'percentage_score' => $percentageScore,
            'exam_total_points' => $examTotalPoints,
            'pending_grading' => $pendingGrading,
            'earned_qcm_points' => $earnedQcmPoints,
            'total_qcm_points' => $totalQcmPoints
        ];
    }
    
    public function updateExamResult($examId, $stagiaireId, $totalScore, $totalPoints) {
        // Calculate percentage score
        $percentageScore = 0;
        if ($totalPoints > 0) {
            $percentageScore = round(($totalScore / $totalPoints) * 100);
        }
        
        $query = "UPDATE results SET 
                  score = :percentage_score, 
                  total_score = :total_score,
                  graded_by = :graded_by
                  WHERE exam_id = :exam_id AND stagiaire_id = :stagiaire_id";
        $stmt = $this->db->prepare($query);
        $params = [
            ':percentage_score' => $percentageScore,
            ':total_score' => $totalScore,
            ':graded_by' => $_SESSION['user_id'] ?? null,
            ':exam_id' => $examId,
            ':stagiaire_id' => $stagiaireId
        ];
        
        return $this->db->execute($stmt, $params);
    }
}
