<?php
require_once 'Model.php';

class Question extends Model {
    protected $table = 'questions';
    
    public function create(array $data) {
        $query = "INSERT INTO questions (exam_id, question_type, question_text, options, correct_answer, points) 
                  VALUES (:exam_id, :question_type, :question_text, :options, :correct_answer, :points)";
                  
        $stmt = $this->db->prepare($query);
        
        // Convert options array to JSON for QCM questions
        $options = isset($data['options']) ? json_encode($data['options']) : null;
        
        $params = [
            ':exam_id' => $data['exam_id'],
            ':question_type' => $data['question_type'],
            ':question_text' => $data['question_text'],
            ':options' => $options,
            ':correct_answer' => $data['correct_answer'],
            ':points' => isset($data['points']) ? $data['points'] : 1
        ];
        
        if ($this->db->execute($stmt, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update(array $data, $id) {
        $query = "UPDATE questions SET 
                  question_type = :question_type, 
                  question_text = :question_text, 
                  options = :options, 
                  correct_answer = :correct_answer,
                  points = :points
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        // Convert options array to JSON for QCM questions
        $options = isset($data['options']) ? json_encode($data['options']) : null;
        
        $params = [
            ':question_type' => $data['question_type'],
            ':question_text' => $data['question_text'],
            ':options' => $options,
            ':correct_answer' => $data['correct_answer'],
            ':points' => isset($data['points']) ? $data['points'] : 1,
            ':id' => $id
        ];
        
        return $this->db->execute($stmt, $params);
    }
    
    public function getQuestionsByExam($examId) {
        $query = "SELECT * FROM questions WHERE exam_id = :exam_id";
        $stmt = $this->db->prepare($query);
        $params = [':exam_id' => $examId];
        $this->db->execute($stmt, $params);
        
        $questions = $this->db->resultSet($stmt);
        
        // Process each question to handle JSON fields
        foreach ($questions as &$question) {
            // Convert JSON options string to PHP array
            if (!empty($question['options'])) {
                $question['options'] = json_decode($question['options'], true);
            }
            
            // Convert JSON correct_answer string to PHP array for QCM questions
            if ($question['question_type'] === 'qcm' && !empty($question['correct_answer'])) {
                // Check if it's already a JSON string (for backward compatibility)
                if ($question['correct_answer'][0] === '[' || $question['correct_answer'][0] === '{') {
                    $question['correct_answer'] = json_decode($question['correct_answer'], true);
                } else {
                    // Handle legacy single-answer format
                    $question['correct_answer'] = [$question['correct_answer']];
                }
            }
        }
        
        return $questions;
    }
    
    public function getQuestionsByExamWithPoints($examId) {
        $query = "SELECT q.*, 
                  (SELECT COUNT(*) FROM questions WHERE exam_id = :exam_id) as total_questions,
                  (SELECT total_points FROM exams WHERE id = :exam_id) as exam_total_points
                  FROM questions q 
                  WHERE q.exam_id = :exam_id";
        $stmt = $this->db->prepare($query);
        $params = [':exam_id' => $examId];
        $this->db->execute($stmt, $params);
        
        $questions = $this->db->resultSet($stmt);
        
        // Process each question to handle JSON fields
        foreach ($questions as &$question) {
            // Convert JSON options string to PHP array
            if (!empty($question['options'])) {
                $question['options'] = json_decode($question['options'], true);
            }
            
            // Convert JSON correct_answer string to PHP array for QCM questions
            if ($question['question_type'] === 'qcm' && !empty($question['correct_answer'])) {
                // Check if it's already a JSON string (for backward compatibility)
                if ($question['correct_answer'][0] === '[' || $question['correct_answer'][0] === '{') {
                    $question['correct_answer'] = json_decode($question['correct_answer'], true);
                } else {
                    // Handle legacy single-answer format
                    $question['correct_answer'] = [$question['correct_answer']];
                }
            }
        }
        
        return $questions;
    }
    
    public function getById($id) {
        $query = "SELECT * FROM questions WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $params = [':id' => $id];
        $this->db->execute($stmt, $params);
        
        $question = $this->db->single($stmt);
        
        if ($question) {
            // Convert JSON options string to PHP array
            if (!empty($question['options'])) {
                $question['options'] = json_decode($question['options'], true);
            }
            
            // Convert JSON correct_answer string to PHP array for QCM questions
            if ($question['question_type'] === 'qcm' && !empty($question['correct_answer'])) {
                // Check if it's already a JSON string (for backward compatibility)
                if ($question['correct_answer'][0] === '[' || $question['correct_answer'][0] === '{') {
                    $question['correct_answer'] = json_decode($question['correct_answer'], true);
                } else {
                    // Handle legacy single-answer format
                    $question['correct_answer'] = [$question['correct_answer']];
                }
            }
        }
        
        return $question;
    }
    
    public function delete($id) {
        $query = "DELETE FROM questions WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $params = [':id' => $id];
        return $this->db->execute($stmt, $params);
    }
    
    public function calculateDefaultPoints($examId) {
        // Get total points and question count
        $query = "SELECT e.total_points, COUNT(q.id) as question_count 
                 FROM exams e 
                 LEFT JOIN questions q ON e.id = q.exam_id 
                 WHERE e.id = :exam_id 
                 GROUP BY e.id";
        $stmt = $this->db->prepare($query);
        $params = [':exam_id' => $examId];
        $this->db->execute($stmt, $params);
        
        $result = $this->db->single($stmt);
        
        if ($result && $result['question_count'] > 0) {
            return round($result['total_points'] / $result['question_count'], 2);
        }
        
        // Default to 1 point if no questions yet or calculation fails
        return 1;
    }
    
    public function updateQuestionPoints($examId, $defaultPoints = null) {
        if ($defaultPoints === null) {
            $defaultPoints = $this->calculateDefaultPoints($examId);
        }
        
        $query = "UPDATE questions SET points = :points WHERE exam_id = :exam_id AND question_type = 'qcm'";
        $stmt = $this->db->prepare($query);
        $params = [
            ':points' => $defaultPoints,
            ':exam_id' => $examId
        ];
        
        return $this->db->execute($stmt, $params);
    }
}
